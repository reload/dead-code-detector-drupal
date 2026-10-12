<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests;

use PHPStan\Testing\PHPStanTestCase;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFilesResultCacheMetaExtension;

use function array_filter;

final class ExtensionWiringTest extends PHPStanTestCase
{
    public function testResultCacheIsInvalidatedByDrupalWiringFiles(): void
    {
        $extensions = self::getContainer()->getServicesByTag('phpstan.resultCacheMetaExtension');

        self::assertNotEmpty(array_filter(
            $extensions,
            static fn (mixed $extension): bool => $extension instanceof DrupalFilesResultCacheMetaExtension,
        ));
    }

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../vendor/shipmonk/dead-code-detector/rules.neon',
            __DIR__ . '/../extension.neon',
            __DIR__ . '/rule-test.neon',
        ];
    }
}
