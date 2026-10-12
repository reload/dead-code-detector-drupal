<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

/**
 * One analysis for the providers that read YAML wiring, whose fixture modules
 * live in namespaces of their own.
 */
abstract class WiringRuleTestCase extends DeadCodeRuleTestCase
{
    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/drupal-files.neon'];
    }

    protected static function analysedFixtureFiles(): array
    {
        $tests = __DIR__ . '/../..';

        return [
            $tests . '/Core/Services/data/example/src/services.php',
            $tests . '/Core/Routing/data/example/src/routes.php',
            $tests . '/Core/ConfigSchema/data/example/src/schema-callbacks.php',
        ];
    }
}
