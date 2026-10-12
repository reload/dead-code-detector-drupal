<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Routing;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;
use Reload\DeadCodeDetectorDrupal\Core\Routing\RouteDefinitions;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceDefinitions;
use Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage\DeclaredMethods;

final class RouteDefinitionsTest extends TestCase
{
    public function testControllerMethodIsCalledOnClassResolverInstance(): void
    {
        $usages = self::parse(<<<'YAML'
            example.page:
              path: /example
              defaults:
                _controller: '\Drupal\example\RouteController::page'
            YAML);

        self::assertSame(['__construct', 'page'], DeclaredMethods::of($usages, 'Drupal\example\RouteController'));
    }

    public function testTitleCallbackIsCalled(): void
    {
        $usages = self::parse(<<<'YAML'
            example.page:
              path: /example
              defaults:
                _title_callback: 'Drupal\example\TitleResolver::title'
            YAML);

        self::assertSame(['__construct', 'title'], DeclaredMethods::of($usages, 'Drupal\example\TitleResolver'));
    }

    public function testCustomAccessRequirementIsCalled(): void
    {
        $usages = self::parse(<<<'YAML'
            example.page:
              path: /example
              requirements:
                _custom_access: '\Drupal\example\AccessChecker::access'
            YAML);

        self::assertSame(['__construct', 'access'], DeclaredMethods::of($usages, 'Drupal\example\AccessChecker'));
    }

    public function testFormIsInstantiatedByClassResolver(): void
    {
        $usages = self::parse(<<<'YAML'
            example.form:
              path: /example/form
              defaults:
                _form: '\Drupal\example\Form\RoutedForm'
            YAML);

        self::assertSame(['__construct'], DeclaredMethods::of($usages, 'Drupal\example\Form\RoutedForm'));
    }

    public function testRouteCallbacksAreCalled(): void
    {
        $usages = self::parse(<<<'YAML'
            route_callbacks:
              - '\Drupal\example\DynamicRoutes::routes'
            YAML);

        self::assertSame(['__construct', 'routes'], DeclaredMethods::of($usages, 'Drupal\example\DynamicRoutes'));
    }

    public function testEntityFormIsNotAClass(): void
    {
        $usages = self::parse(<<<'YAML'
            example.edit:
              path: /example/edit
              defaults:
                _entity_form: 'example_entity.edit'
            YAML);

        self::assertTrue($usages->isEmpty());
    }

    private static function parse(string $yaml): DeclaredUsages
    {
        return RouteDefinitions::parse(['/site/example/example.routing.yml' => $yaml], ServiceDefinitions::parse([]));
    }
}
