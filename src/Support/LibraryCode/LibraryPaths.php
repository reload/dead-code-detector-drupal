<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\LibraryCode;

use Composer\InstalledVersions;
use Symfony\Component\Filesystem\Path;

use function array_keys;
use function dirname;
use function is_array;
use function is_string;
use function realpath;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Directories of Composer-installed packages: code the site does not own.
 *
 * @see \Composer\InstalledVersions::getAllRawData()
 */
final class LibraryPaths
{
    /**
     * @param array<string, string> $packageNameByDirectory
     *   Canonical, non-empty directories.
     * @phpstan-pure
     */
    private function __construct(
        private readonly array $packageNameByDirectory,
    ) {
    }

    /**
     * @param array<string, string> $packageNameByDirectory
     * @phpstan-pure
     */
    public static function fromDirectories(array $packageNameByDirectory): self
    {
        $packageNameByCanonicalDirectory = [];
        foreach ($packageNameByDirectory as $directory => $packageName) {
            $canonicalDirectory = self::canonicalize($directory);
            // An empty directory would prefix, and so claim, every absolute path.
            if ($canonicalDirectory !== '') {
                $packageNameByCanonicalDirectory[$canonicalDirectory] = $packageName;
            }
        }

        return new self($packageNameByCanonicalDirectory);
    }

    public static function read(): self
    {
        $libraryPaths = self::parse(InstalledVersions::getAllRawData());

        $realDirectoryByDirectory = [];
        foreach (array_keys($libraryPaths->packageNameByDirectory) as $directory) {
            $realDirectory = realpath($directory);
            if ($realDirectory !== false) {
                $realDirectoryByDirectory[$directory] = $realDirectory;
            }
        }

        return $libraryPaths->withRealDirectories($realDirectoryByDirectory);
    }

    /**
     * @param array<mixed> $installedData
     *   Datasets as returned by InstalledVersions::getAllRawData().
     * @phpstan-pure
     */
    public static function parse(array $installedData): self
    {
        $packageNameByDirectory = [];
        foreach ($installedData as $dataset) {
            if (!is_array($dataset) || !is_array($dataset['versions'] ?? null)) {
                continue;
            }

            $root = $dataset['root'] ?? null;
            $rootDirectory = is_array($root) && is_string($root['install_path'] ?? null)
                ? self::canonicalize($root['install_path'])
                : null;

            foreach ($dataset['versions'] as $packageName => $package) {
                // Metapackages, and packages only provided or replaced, have no directory.
                if (!is_string($packageName) || !is_array($package) || !is_string($package['install_path'] ?? null)) {
                    continue;
                }

                $directory = self::canonicalize($package['install_path']);
                if ($directory !== $rootDirectory) {
                    $packageNameByDirectory[$directory] = $packageName;
                }
            }
        }

        return self::fromDirectories($packageNameByDirectory);
    }

    /**
     * Adds the real directories of symlinked install paths, such as Composer
     * path repositories: reflection may report either path.
     *
     * @param array<string, string> $realDirectoryByDirectory
     */
    public function withRealDirectories(array $realDirectoryByDirectory): self
    {
        $packageNameByDirectory = $this->packageNameByDirectory;
        foreach ($realDirectoryByDirectory as $directory => $realDirectory) {
            $packageName = $this->packageNameByDirectory[self::canonicalize($directory)] ?? null;
            $realDirectory = self::canonicalize($realDirectory);
            if ($packageName !== null && !isset($packageNameByDirectory[$realDirectory])) {
                $packageNameByDirectory[$realDirectory] = $packageName;
            }
        }

        return self::fromDirectories($packageNameByDirectory);
    }

    /**
     * Called per member of every analysed class, so it walks up the file's
     * directories rather than over every install path. The first one found is
     * the most specific when install paths nest.
     */
    public function findPackageName(string $filePath): ?string
    {
        $directory = dirname(self::canonicalize($filePath));
        while (!isset($this->packageNameByDirectory[$directory])) {
            $parentDirectory = dirname($directory);
            if ($parentDirectory === $directory) {
                return null;
            }

            $directory = $parentDirectory;
        }

        return $this->packageNameByDirectory[$directory];
    }

    /**
     * Without "phar://", so a file inside a phar belongs to the package
     * directory the phar is in.
     *
     * @phpstan-pure
     */
    private static function canonicalize(string $path): string
    {
        // @phpstan-ignore possiblyImpure.methodCall (Symfony's Path only computes)
        return Path::canonicalize(str_starts_with($path, 'phar://') ? substr($path, strlen('phar://')) : $path);
    }
}
