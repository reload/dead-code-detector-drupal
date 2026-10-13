<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Discovery;

use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;

use function hash;
use function hash_file;
use function implode;
use function sort;

/**
 * Invalidates PHPStan's result cache when Drupal's wiring or settings files
 * change.
 *
 * Usages read from YAML and Twig are cached with the PHP file they are
 * attached to, so without this an edited routing.yml leaves stale results.
 */
final class DrupalFilesResultCacheMetaExtension implements ResultCacheMetaExtension
{
    public function __construct(
        private readonly DrupalFiles $drupalFiles,
        private readonly SettingsFiles $settingsFiles,
    ) {
    }

    public function getKey(): string
    {
        return 'reload/dead-code-detector-drupal';
    }

    public function getHash(): string
    {
        $fileFacts = [];
        foreach ($this->drupalFiles->relativePathByFilePath() as $filePath => $relativePath) {
            // A wiring file's place in the tree counts too: the tests excluder judges usages by it.
            $fileFacts[] = implode(' ', [$relativePath, hash_file('sha256', $filePath) ?: '']);
        }

        foreach ($this->settingsFiles->all() as $filePath) {
            $fileFacts[] = hash_file('sha256', $filePath) ?: '';
        }

        return self::combinedHash($fileFacts);
    }

    /**
     * Independent of the order files are found in.
     *
     * @param list<string> $fileFacts
     *   A string for each file, from what the providers depend on.
     */
    public static function combinedHash(array $fileFacts): string
    {
        sort($fileFacts);

        return hash('sha256', implode("\n", $fileFacts));
    }
}
