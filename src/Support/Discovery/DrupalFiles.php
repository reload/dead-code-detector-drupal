<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Discovery;

use Symfony\Component\Finder\Finder;

use function array_filter;
use function array_keys;
use function array_map;
use function basename;
use function file_get_contents;
use function is_dir;
use function is_file;
use function ksort;
use function str_ends_with;

use const ARRAY_FILTER_USE_KEY;

/**
 * Drupal's non-PHP wiring files (YAML, Twig) under PHPStan's analysed paths.
 *
 * Custom code keeps its wiring next to its PHP, so the analysed paths are
 * exactly where to look.
 */
final class DrupalFiles
{
    public const SERVICES = '.services.yml';
    public const ROUTING = '.routing.yml';
    // Not only *.html.twig: single-directory components' templates are <name>.twig.
    public const TWIG_TEMPLATE = '.twig';
    public const CONFIG_SCHEMA = '.schema.yml';
    public const PERMISSIONS = '.permissions.yml';


    // Finder skips .git and other VCS directories itself.
    private const SKIPPED_DIRECTORIES = ['node_modules', 'vendor'];

    /**
     * @var array<string, string>|null
     *   File path => its path relative to the analysed path it was found under.
     */
    private ?array $relativePathByFilePath = null;

    /**
     * @var list<string>
     */
    private readonly array $suffixes;

    /**
     * @param list<string> $analysedPaths
     * @param bool $twigTemplatesEnabled
     *   Off, no templates are found, so nothing reads or hashes them.
     */
    public function __construct(
        private readonly array $analysedPaths,
        private readonly ExcludedPaths $excludedPaths = new ExcludedPaths(),
        bool $twigTemplatesEnabled = true,
    ) {
        $this->suffixes = [
            self::SERVICES,
            self::ROUTING,
            self::CONFIG_SCHEMA,
            self::PERMISSIONS,
            ...($twigTemplatesEnabled ? [self::TWIG_TEMPLATE] : []),
        ];
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return array_keys($this->relativePathByFilePath());
    }

    /**
     * @return array<string, string>
     *   File path => its path relative to the analysed path it was found under.
     */
    public function relativePathByFilePath(): array
    {
        return $this->relativePathByFilePath ??= $this->find();
    }

    /**
     * @return array<string, string>
     *   Contents keyed by file path.
     */
    public function readWithSuffix(string $suffix): array
    {
        $contentsByFilePath = [];
        foreach ($this->all() as $filePath) {
            if (str_ends_with($filePath, $suffix)) {
                $contents = file_get_contents($filePath);
                if ($contents !== false) {
                    $contentsByFilePath[$filePath] = $contents;
                }
            }
        }

        return $contentsByFilePath;
    }

    /**
     * @return array<string, string>
     */
    private function find(): array
    {
        $relativePathByFilePath = [];
        $directories = [];
        foreach ($this->analysedPaths as $analysedPath) {
            if (is_file($analysedPath)) {
                if ($this->hasWiringSuffix($analysedPath)) {
                    $relativePathByFilePath[$analysedPath] = basename($analysedPath);
                }
            } elseif (is_dir($analysedPath)) {
                $directories[] = $analysedPath;
            }
        }

        if ($directories !== []) {
            $wiringFiles = Finder::create()
                ->files()
                ->in($directories)
                ->name(array_map(static fn (string $suffix): string => '*' . $suffix, $this->suffixes))
                ->exclude(self::SKIPPED_DIRECTORIES)
                // PHPStan analyses symlinked directories too.
                ->followLinks();
            foreach ($wiringFiles as $wiringFile) {
                $relativePathByFilePath[$wiringFile->getPathname()] = $wiringFile->getRelativePathname();
            }
        }

        $relativePathByFilePath = array_filter(
            $relativePathByFilePath,
            fn (string $filePath): bool => !$this->excludedPaths->contains($filePath),
            ARRAY_FILTER_USE_KEY,
        );
        ksort($relativePathByFilePath);

        return $relativePathByFilePath;
    }

    private function hasWiringSuffix(string $filePath): bool
    {
        foreach ($this->suffixes as $suffix) {
            if (str_ends_with($filePath, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
