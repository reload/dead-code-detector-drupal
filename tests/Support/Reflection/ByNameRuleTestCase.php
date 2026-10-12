<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

/**
 * One analysis for the providers built on the by-name bases, whose fixtures
 * live in namespaces of their own.
 */
abstract class ByNameRuleTestCase extends DeadCodeRuleTestCase
{
    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/by-name-providers.neon'];
    }

    protected static function analysedFixtureFiles(): array
    {
        $tests = __DIR__ . '/../..';

        return [
            $tests . '/Core/Access/data/access-checkers.php',
            $tests . '/Core/ConfigEntity/data/config-entities.php',
            $tests . '/Core/EventSubscriber/data/exception-subscribers.php',
            $tests . '/Core/Plugin/data/attributes.php',
            $tests . '/Core/Rest/data/resources.php',
            $tests . '/Core/Validation/data/constraints.php',
            $tests . '/Contrib/GraphQl/data/data-producers.php',
            $tests . '/Contrib/GraphQl/data/stubs.php',
            $tests . '/Contrib/JsonApi/data/resources.php',
            $tests . '/Contrib/JsonApi/data/stubs.php',
        ];
    }
}
