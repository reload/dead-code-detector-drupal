<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Routing;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class RouteAttributeUsageProviderTest extends DeadCodeRuleTestCase
{
    private const CONTROLLER_NAMESPACE = 'Drupal\example\Controller';

    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/attribute-routes.php'];
    }

    /**
     * Core finds attribute routes in a module's Controller namespace.
     */
    protected static function fixtureNamespace(): string
    {
        return 'Drupal\example\Controller';
    }

    public static function usedMembers(): iterable
    {
        yield 'constructor of controller routed by a method'
            => [self::CONTROLLER_NAMESPACE . '\ControllerRoutedByMethod::__construct'];
        yield 'constructor of invokable controller routed by its class'
            => [self::CONTROLLER_NAMESPACE . '\InvokableControllerRoutedByClass::__construct'];
        yield 'constructor a routed controller inherits'
            => [self::CONTROLLER_NAMESPACE . '\BaseOfRoutedController::__construct'];
        yield 'constructor of controller inheriting its routed method'
            => [self::CONTROLLER_NAMESPACE . '\ControllerInheritingRoutedMethod::__construct'];
        yield 'constructor of routed controller inheriting built-in methods'
            => [self::CONTROLLER_NAMESPACE . '\RoutedControllerInheritingBuiltInMethods::__construct'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'constructor of controller without routes'
            => ['Unused ' . self::CONTROLLER_NAMESPACE . '\ControllerWithoutRoutes::__construct'];
        yield 'constructor of abstract routed controller'
            => ['Unused ' . self::CONTROLLER_NAMESPACE . '\AbstractRoutedController::__construct'];
        yield 'constructor of class-routed controller without __invoke'
            => ['Unused ' . self::CONTROLLER_NAMESPACE . '\ClassRoutedControllerWithoutInvoke::__construct'];
    }
}
