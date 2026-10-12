<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Discovery;

use Composer\InstalledVersions;
use Symfony\Component\Filesystem\Path;

use function basename;
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

    /**
     * Only the Twig sandbox reads settings, so with Twig matching off there
     * are none to find.
     */
    public static function forCore(?string $corePath, bool $twigTemplatesEnabled): self
    {
        return $corePath === null || !$twigTemplatesEnabled ? new self([]) : self::besideCore($corePath);
    }

    /**
     * Core lives in the Drupal root. Normalised without resolving symlinks, so
     * a symlinked core still leads to the site's directories.
     */
    public static function besideCore(string $corePath): self
    {
        return self::inDrupalRoot(Path::getDirectory($corePath));
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
}
