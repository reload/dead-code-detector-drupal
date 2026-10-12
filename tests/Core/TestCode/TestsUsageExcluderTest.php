<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\TestCode;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class TestsUsageExcluderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/test-paths.neon'];
    }

    protected static function fixtureFiles(): array
    {
        return [
            __DIR__ . '/data/module/src/ProductionCode.php',
            __DIR__ . '/data/module/tests/src/CallsFromTests.php',
            __DIR__ . '/data/module/tests/modules/example_test/src/Controller/TestModuleController.php',
        ];
    }

    public static function usedMembers(): iterable
    {
        yield 'production code called from production code'
            => ['Drupal\example\ProductionCode::calledFromProductionCode'];
        yield 'test code called from tests' => ['Drupal\Tests\example\TestHelper::helperCalledFromTests'];
        yield 'production code routed by its module' => ['Drupal\example\ProductionCode::routedByModule'];
        yield 'test code routed by its test module'
            => ['Drupal\example_test\Controller\TestModuleController::routedByTestModule'];
        yield 'production code shown by a template outside tests too'
            => ['Drupal\example\ProductionCode::getShownByModuleAndTestTheme'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'production code called only from tests'
            => [
                'Unused Drupal\example\ProductionCode::calledOnlyFromTests'
                    . ' (all usages excluded by drupalTests excluder)',
            ];
        yield 'production code routed only by a test module'
            => [
                'Unused Drupal\example\ProductionCode::routedOnlyByTestModule'
                    . ' (all usages excluded by drupalTests excluder)',
            ];
        yield 'production code shown only by a test theme'
            => [
                'Unused Drupal\example\ProductionCode::getShownOnlyByTestTheme'
                    . ' (all usages excluded by drupalTests excluder)',
            ];
        yield 'production code called by nothing' => ['Unused Drupal\example\ProductionCode::calledByNothing'];
    }
}
