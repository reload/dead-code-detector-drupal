<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests;

use Reload\DeadCodeDetectorDrupal\Support\Discovery\SettingsFiles;

/**
 * Settings files for rule tests; neon cannot express paths relative to a test.
 */
final class FixtureSettingsFiles
{
    public static function siteReplacingAllowedMethods(): SettingsFiles
    {
        return SettingsFiles::inDrupalRoot(__DIR__ . '/Core/Settings/data/site-replacing-allowed-methods');
    }
}
