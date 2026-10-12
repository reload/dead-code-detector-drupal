<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Callable;

use Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage\DeclaredMethods;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;
use Reload\DeadCodeDetectorDrupal\Core\Callable\DrupalCallables;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceDefinitions;

final class DrupalCallablesTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function callables(): iterable
    {
        yield 'Class::method, on an instance the class resolver builds'
            => ['\Drupal\example\ResolvedClass::method', 'Drupal\example\ResolvedClass', ['__construct', 'method']];
        yield 'Class::method without a leading backslash'
            => ['Drupal\example\ResolvedClass::method', 'Drupal\example\ResolvedClass', ['__construct', 'method']];
        yield 'Class:method, which the class resolver also takes'
            => ['\Drupal\example\ResolvedClass:method', 'Drupal\example\ResolvedClass', ['__construct', 'method']];
        yield 'service:method, on the service, which the container builds'
            => ['example.service:method', 'Drupal\example\ServiceClass', ['method']];
        yield 'invokable Class'
            => ['Drupal\example\InvokableClass', 'Drupal\example\InvokableClass', ['__construct', '__invoke']];
    }

    /**
     * @param list<string> $expectedMethodNames
     */
    #[DataProvider('callables')]
    public function testCallableDeclaresMethods(string $definition, string $className, array $expectedMethodNames): void
    {
        self::assertSame($expectedMethodNames, DeclaredMethods::of(self::declare($definition), $className));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonCallables(): iterable
    {
        yield 'service:method of a service no analysed file defines' => ['library.service:method'];
        yield 'function name' => ['example_function'];
        yield 'value other than a string' => [['Drupal\example\ResolvedClass', 'method']];
    }

    #[DataProvider('nonCallables')]
    public function testOtherValueDeclaresNothing(mixed $definition): void
    {
        self::assertTrue(self::declare($definition)->isEmpty());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function definitionsWithAnEmptySide(): iterable
    {
        yield 'no method after "::"' => ['Drupal\example\ResolvedClass::'];
        yield 'no class before "::"' => ['::method'];
        yield 'no method after ":"' => ['example.service:'];
    }

    #[DataProvider('definitionsWithAnEmptySide')]
    public function testDefinitionWithAnEmptySideDoesNotSplit(string $definition): void
    {
        self::assertNull(DrupalCallables::split($definition));
    }

    private static function declare(mixed $definition): DeclaredUsages
    {
        $services = ServiceDefinitions::parse(['/site/example/example.services.yml' => <<<'YAML'
            services:
              example.service:
                class: Drupal\example\ServiceClass
            YAML]);

        return DrupalCallables::declare(
            DeclaredUsages::none(),
            $definition,
            $services,
            'Callable in example.routing.yml',
            '/site/example/example.routing.yml',
        );
    }
}
