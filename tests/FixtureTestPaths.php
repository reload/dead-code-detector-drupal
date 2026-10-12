<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests;

use Reload\DeadCodeDetectorDrupal\Core\TestCode\TestPaths;

/**
 * Analysed paths for the tests excluder's rule test.
 */
final class FixtureTestPaths
{
    public static function module(): TestPaths
    {
        return new TestPaths([__DIR__ . '/Core/TestCode/data']);
    }
}
