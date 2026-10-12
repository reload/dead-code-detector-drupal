<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\TestCode;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class TestsUsageExcluderDisabledTest extends DeadCodeRuleTestCase
{
    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/test-paths.neon', __DIR__ . '/data/tests-excluder-disabled.neon'];
    }

    protected static function fixtureFiles(): array
    {
        return [
            __DIR__ . '/data/module/src/ProductionCode.php',
            __DIR__ . '/data/module/tests/src/CallsFromTests.php',
        ];
    }

    public static function usedMembers(): iterable
    {
        yield 'production code called only from tests' => ['Drupal\example\ProductionCode::calledOnlyFromTests'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'production code called by nothing' => ['Unused Drupal\example\ProductionCode::calledByNothing'];
    }
}
