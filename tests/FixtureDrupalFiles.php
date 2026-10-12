<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests;

use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFiles;

/**
 * Analysed paths for rule tests; neon cannot express paths relative to a test.
 */
final class FixtureDrupalFiles
{
    public static function declaredUsage(): DrupalFiles
    {
        return new DrupalFiles([
            __DIR__ . '/Core/Services/data',
            __DIR__ . '/Core/Routing/data',
            __DIR__ . '/Core/ConfigSchema/data',
            __DIR__ . '/Core/Permission/data',
        ]);
    }

    public static function callable(): DrupalFiles
    {
        return new DrupalFiles([__DIR__ . '/Core/Callable/data']);
    }

    public static function twig(): DrupalFiles
    {
        return new DrupalFiles([__DIR__ . '/Core/Twig/data/templates']);
    }

    public static function twigDisabled(bool $twigTemplatesEnabled): DrupalFiles
    {
        return new DrupalFiles([__DIR__ . '/Core/Twig/data/disabled'], twigTemplatesEnabled: $twigTemplatesEnabled);
    }

    public static function testsExcluder(): DrupalFiles
    {
        return new DrupalFiles([__DIR__ . '/Core/TestCode/data']);
    }

    public static function siteReplacingAllowedMethods(): DrupalFiles
    {
        return new DrupalFiles([__DIR__ . '/Core/Settings/data/site-replacing-allowed-methods']);
    }
}
