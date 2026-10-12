<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Routing;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class RouteUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/example/src/routes.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'controller method' => ['Drupal\example\Routing\RouteController::page'];
        yield 'controller constructor, as the class resolver instantiates it'
            => ['Drupal\example\Routing\RouteController::__construct'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'controller method no route names'
            => ['Unused Drupal\example\Routing\RouteController::methodNothingCalls'];
    }
}
