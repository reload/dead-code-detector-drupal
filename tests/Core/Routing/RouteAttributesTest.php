<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Core\Routing\RouteAttributes;

final class RouteAttributesTest extends TestCase
{
    private const ROUTE = 'Symfony\Component\Routing\Attribute\Route';

    /**
     * @param list<string> $classAttributeNames
     * @param list<list<string>> $methodAttributeNames
     */
    #[DataProvider('classesWithRoutes')]
    public function testDeclaresRoutes(
        string $className,
        bool $isAbstract,
        bool $isInvokable,
        array $classAttributeNames,
        array $methodAttributeNames,
    ): void {
        self::assertTrue(RouteAttributes::declaresRoutes(
            $className,
            $isAbstract,
            $isInvokable,
            $classAttributeNames,
            $methodAttributeNames,
        ));
    }

    /**
     * @param list<string> $classAttributeNames
     * @param list<list<string>> $methodAttributeNames
     */
    #[DataProvider('classesWithoutRoutes')]
    public function testDeclaresNoRoutes(
        string $className,
        bool $isAbstract,
        bool $isInvokable,
        array $classAttributeNames,
        array $methodAttributeNames,
    ): void {
        self::assertFalse(RouteAttributes::declaresRoutes(
            $className,
            $isAbstract,
            $isInvokable,
            $classAttributeNames,
            $methodAttributeNames,
        ));
    }

    /**
     * @return iterable<string, array{string, bool, bool, list<string>, list<list<string>>}>
     */
    public static function classesWithRoutes(): iterable
    {
        yield 'route on a method' => self::controller();
        yield 'deprecated route annotation class on a method'
            => self::controller(methodAttributeNames: [['Symfony\Component\Routing\Annotation\Route']]);
        yield 'class-level route on an invokable class'
            => self::controller(isInvokable: true, classAttributeNames: [self::ROUTE], methodAttributeNames: [[]]);
        yield 'controller in a sub-namespace' => self::controller(className: 'Drupal\example\Controller\Admin\Page');
    }

    /**
     * @return iterable<string, array{string, bool, bool, list<string>, list<list<string>>}>
     */
    public static function classesWithoutRoutes(): iterable
    {
        yield 'no route attribute' => self::controller(methodAttributeNames: [[]]);
        yield 'class-level route without __invoke'
            => self::controller(classAttributeNames: [self::ROUTE], methodAttributeNames: [[]]);
        yield 'invokable class without a route' => self::controller(isInvokable: true, methodAttributeNames: [[]]);
        yield 'abstract class' => self::controller(isAbstract: true);
        yield 'outside a Controller namespace' => self::controller(className: 'Drupal\example\Form\Page');
        yield 'class named Controller, not inside its namespace'
            => self::controller(className: 'Drupal\example\Controller');
        yield 'outside the Drupal namespace' => self::controller(className: 'Vendor\example\Controller\Page');
    }

    /**
     * A controller with a method route; each case changes one thing.
     *
     * @param list<string> $classAttributeNames
     * @param list<list<string>> $methodAttributeNames
     * @return array{string, bool, bool, list<string>, list<list<string>>}
     */
    private static function controller(
        string $className = 'Drupal\example\Controller\Page',
        bool $isAbstract = false,
        bool $isInvokable = false,
        array $classAttributeNames = [],
        array $methodAttributeNames = [[self::ROUTE]],
    ): array {
        return [$className, $isAbstract, $isInvokable, $classAttributeNames, $methodAttributeNames];
    }
}
