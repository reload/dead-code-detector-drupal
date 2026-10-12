<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceDefinitions;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceWiring;
use Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage\DeclaredMethods;

use function array_diff;
use function array_values;

final class ServiceDefinitionsTest extends TestCase
{
    public function testServiceClassIsConstructedByContainer(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.constructed:
                class: Drupal\example\ConstructedService
            YAML);

        self::assertSame(['__construct'], self::declared($services, 'Drupal\example\ConstructedService'));
    }

    public function testClassNameAsServiceIdWithoutDefinitionIsConstructed(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              Drupal\example\ServiceWithoutDefinition: ~
            YAML);

        self::assertSame(['__construct'], self::declared($services, 'Drupal\example\ServiceWithoutDefinition'));
    }

    public function testFactoryBuiltServiceIsNotConstructedByContainer(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.factory_built:
                class: Drupal\example\FactoryBuiltService
                factory: Drupal\example\StaticServiceFactory::createService
            YAML);

        self::assertSame([], self::declared($services, 'Drupal\example\FactoryBuiltService'));
        self::assertSame(['createService'], self::declared($services, 'Drupal\example\StaticServiceFactory'));
    }

    public function testFactoryReferencingServiceIsCalledOnThatServicesClass(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.service_factory:
                class: Drupal\example\ServiceFactory
              example.factory_built:
                class: Drupal\example\FactoryBuiltService
                factory: ['@example.service_factory', 'createService']
            YAML);

        self::assertSame(['__construct', 'createService'], self::declared($services, 'Drupal\example\ServiceFactory'));
    }

    public function testFactoryInServiceColonMethodNotationIsCalledOnThatServicesClass(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.service_factory:
                class: Drupal\example\ServiceFactory
              example.factory_built:
                class: Drupal\example\FactoryBuiltService
                factory: example.service_factory:createService
            YAML);

        self::assertSame(['__construct', 'createService'], self::declared($services, 'Drupal\example\ServiceFactory'));
    }

    public function testCallsInEveryNotationAreCalledByContainer(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.with_setters:
                class: Drupal\example\ServiceWithSetters
                calls:
                  - [setByList, ['@logger']]
                  - { method: setByMethodKey, arguments: ['@logger'] }
                  - setByMethodName: ['@logger']
            YAML);

        self::assertSame(
            ['__construct', 'setByList', 'setByMethodKey', 'setByMethodName'],
            self::declared($services, 'Drupal\example\ServiceWithSetters'),
        );
    }

    public function testConfiguratorIsCalledByContainer(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.configured:
                class: Drupal\example\ConfiguredService
                configurator: ['Drupal\example\ServiceConfigurator', 'configure']
            YAML);

        self::assertSame(['configure'], self::declared($services, 'Drupal\example\ServiceConfigurator'));
    }

    public function testChildServiceInheritsClassOfAbstractParent(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.template:
                abstract: true
                class: Drupal\example\TemplateClass
              example.child:
                parent: example.template
            YAML);

        self::assertSame(['__construct'], self::declared($services, 'Drupal\example\TemplateClass'));
        self::assertSame('Drupal\example\TemplateClass', $services->classOf('example.child'));
    }

    public function testAbstractServiceIsNotConstructed(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.template:
                abstract: true
                class: Drupal\example\OnlyAbstractTemplate
            YAML);

        self::assertSame([], self::declared($services, 'Drupal\example\OnlyAbstractTemplate'));
    }

    public function testAliasesDeclareNothing(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              Drupal\example\AliasedByShorthand: '@example.target'
              Drupal\example\AliasedByKey:
                alias: example.target
            YAML);

        self::assertTrue($services->declaredUsages()->isEmpty());
        self::assertNull($services->classOf('Drupal\example\AliasedByKey'));
    }

    public function testInvokableServiceFactoryIsInvoked(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.invokable_factory:
                class: Drupal\example\InvokableFactory
              example.factory_built:
                class: Drupal\example\FactoryBuiltService
                factory: '@example.invokable_factory'
            YAML);

        self::assertSame(['__construct', '__invoke'], self::declared($services, 'Drupal\example\InvokableFactory'));
    }

    public function testParentCycleResolvesNoClass(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.cycle_a:
                parent: example.cycle_b
              example.cycle_b:
                parent: example.cycle_a
            YAML);

        self::assertNull($services->classOf('example.cycle_a'));
    }

    public function testCustomYamlTagsDoNotStopParsing(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.with_tagged_iterator:
                class: Drupal\example\ServiceWithTaggedIterator
                arguments: [!tagged_iterator example_tag]
            YAML);

        self::assertSame(['__construct'], self::declared($services, 'Drupal\example\ServiceWithTaggedIterator'));
    }

    public function testParameterAsClassIsNotAClass(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.parameterised:
                class: '%example.class%'
            YAML);

        self::assertNull($services->classOf('example.parameterised'));
    }

    public function testDefaultsAreNotAService(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              _defaults:
                class: Drupal\example\NotAService
            YAML);

        self::assertTrue($services->declaredUsages()->isEmpty());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function tagsCallingMethods(): iterable
    {
        yield 'access_check calls access() by default'
            => ['{ name: access_check, applies_to: _example_access }', 'access'];
        yield 'access_check names its method' => [
            '{ name: access_check, applies_to: _example_access, method: checkExampleAccess }',
            'checkExampleAccess',
        ];
        yield 'service_collector calls addHandler() by default'
            => ['{ name: service_collector, tag: example_handler }', 'addHandler'];
        yield 'service_collector names its method in "call"'
            => ['{ name: service_collector, tag: example_handler, call: addExampleHandler }', 'addExampleHandler'];
    }

    #[DataProvider('tagsCallingMethods')]
    public function testTagCallsMethod(string $tag, string $expectedMethodName): void
    {
        $services = self::parse(<<<YAML
            services:
              example.tagged:
                class: Drupal\\example\\TaggedService
                tags:
                  - {$tag}
            YAML);

        // The constructor is another rule's; the tag's method must replace its default, not join it.
        self::assertSame(
            [$expectedMethodName],
            array_values(array_diff(self::declared($services, 'Drupal\example\TaggedService'), ['__construct'])),
        );
    }

    public function testEveryAccessCheckTagNamesAMethod(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.access_checker:
                class: Drupal\example\AccessCheckerWithTwoChecks
                tags:
                  - { name: access_check, applies_to: _first_access, method: checkFirst }
                  - { name: access_check, applies_to: _second_access, method: checkSecond }
            YAML);

        self::assertSame(
            ['__construct', 'checkFirst', 'checkSecond'],
            self::declared($services, 'Drupal\example\AccessCheckerWithTwoChecks'),
        );
    }

    public function testOtherTagsCallNothing(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.event_subscriber:
                class: Drupal\example\SubscriberCalledThroughItsInterface
                tags:
                  - { name: event_subscriber }
            YAML);

        self::assertSame(
            ['__construct'],
            self::declared($services, 'Drupal\example\SubscriberCalledThroughItsInterface'),
        );
    }

    public function testCacheContextTagCallsCacheContextMethods(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              cache_context.example:
                class: Drupal\example\DuckTypedCacheContext
                tags:
                  - cache.context
            YAML);

        self::assertSame(
            ['__construct', 'getCacheableMetadata', 'getContext', 'getLabel'],
            self::declared($services, 'Drupal\example\DuckTypedCacheContext'),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function aliases(): iterable
    {
        yield 'alias in "@" notation' => [<<<'YAML'
            services:
              example.service:
                class: Drupal\example\AliasedService
              example.alias: '@example.service'
            YAML];
        yield 'alias by key' => [<<<'YAML'
            services:
              example.service:
                class: Drupal\example\AliasedService
              example.alias: { alias: example.service }
            YAML];
        yield 'alias of an alias' => [<<<'YAML'
            services:
              example.service:
                class: Drupal\example\AliasedService
              example.alias: '@example.other_alias'
              example.other_alias: '@example.service'
            YAML];
    }

    #[DataProvider('aliases')]
    public function testAliasStandsForTheServiceItNames(string $yaml): void
    {
        self::assertSame('Drupal\example\AliasedService', self::parse($yaml)->classOf('example.alias'));
    }

    public function testAliasCycleStandsForNothing(): void
    {
        $services = self::parse(<<<'YAML'
            services:
              example.first: '@example.second'
              example.second: '@example.first'
            YAML);

        self::assertNull($services->classOf('example.first'));
    }

    public function testInvalidYamlDeclaresNothing(): void
    {
        $services = self::parse("services:\n  Drupal\\example\\Broken: [unclosed\n");

        self::assertTrue($services->declaredUsages()->isEmpty());
    }

    private static function parse(string $yaml): ServiceWiring
    {
        return ServiceDefinitions::parse(['/site/example/example.services.yml' => $yaml]);
    }

    /**
     * @return list<string>
     */
    private static function declared(ServiceWiring $services, string $className): array
    {
        return DeclaredMethods::of($services->declaredUsages(), $className);
    }
}
