<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Discovery;

use Composer\InstalledVersions;
use Symfony\Component\Filesystem\Path;

use function basename;
use function file_get_contents;
use function glob;

/**
 * The PHP files in a Drupal site's site directories: settings.php and the
 * files it includes, which conventionally sit next to it.
 *
 * @see \Drupal\Core\Site\Settings
 */
final class SettingsFiles
{
    /**
     * @var array<string, string>|null
     *   Contents keyed by file path, read on first use.
     */
    private ?array $contentsByFilePath = null;

    /**
     * @param list<string> $filePaths
     */
    public function __construct(
        private readonly array $filePaths,
    ) {
    }

    public static function find(bool $twigTemplatesEnabled = true): self
    {
        $corePath = InstalledVersions::isInstalled('drupal/core')
            ? InstalledVersions::getInstallPath('drupal/core')
            : null;

        return self::forCore($corePath, $twigTemplatesEnabled);
    }

    public static function forCore(?string $corePath, bool $twigTemplatesEnabled): self
    {
        $drupalRoot = self::drupalRoot($corePath, $twigTemplatesEnabled);

        return $drupalRoot === null ? new self([]) : self::inDrupalRoot($drupalRoot);
    }

    /**
     * Where to look for settings, if anywhere. Only the Twig sandbox reads
     * them, so with Twig matching off there is nowhere. Core lives in the
     * Drupal root; normalised without resolving symlinks, so a symlinked core
     * still leads to the site's directories.
     *
     * @phpstan-pure
     */
    public static function drupalRoot(?string $corePath, bool $twigTemplatesEnabled): ?string
    {
        return $corePath === null || !$twigTemplatesEnabled
            ? null
            // @phpstan-ignore possiblyImpure.methodCall (an absolute path, as Composer's, reads no home directory)
            : Path::getDirectory($corePath);
    }

    public static function inDrupalRoot(string $drupalRoot): self
    {
        $filePaths = [];
        foreach (glob($drupalRoot . '/sites/*/*.php') ?: [] as $filePath) {
            // A template that Drupal never loads.
            if (basename($filePath) !== 'default.settings.php') {
                $filePaths[] = $filePath;
            }
        }

        return new self($filePaths);
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->filePaths;
    }

    /**
     * @return array<string, string>
     *   Contents keyed by file path; files that cannot be read are left out.
     */
    public function read(): array
    {
        return $this->contentsByFilePath ??= $this->readAll();
    }

    /**
     * @return array<string, string>
     */
    private function readAll(): array
    {
        $contentsByFilePath = [];
        foreach ($this->filePaths as $filePath) {
            $contents = file_get_contents($filePath);
            if ($contents !== false) {
                $contentsByFilePath[$filePath] = $contents;
            }
        }

        return $contentsByFilePath;
    }
}
