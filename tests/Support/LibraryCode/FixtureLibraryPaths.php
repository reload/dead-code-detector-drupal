<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\LibraryCode;

use Reload\DeadCodeDetectorDrupal\Support\LibraryCode\LibraryPaths;

/**
 * Treats data/library as Composer-installed; neon cannot express paths
 * relative to the test.
 */
final class FixtureLibraryPaths
{
    public static function create(): LibraryPaths
    {
        return LibraryPaths::fromDirectories([__DIR__ . '/data/library' => 'fixture/library']);
    }
}
