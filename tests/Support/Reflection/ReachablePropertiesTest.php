<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection;

use Drupal\example\ReachableProperties\ClassWithPropertyOfEachKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\ReachableProperties;

final class ReachablePropertiesTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function properties(): iterable
    {
        yield 'public property' => ['publicProperty', true];
        yield 'protected property' => ['protectedProperty', true];
        yield 'private property, which the base class cannot see' => ['privateProperty', false];
        yield 'static property' => ['staticProperty', false];
    }

    #[DataProvider('properties')]
    public function testBaseClassReachesProperty(string $propertyName, bool $expectedToBeReachable): void
    {
        self::assertSame(
            $expectedToBeReachable,
            ReachableProperties::includes(new ReflectionProperty(ClassWithPropertyOfEachKind::class, $propertyName)),
        );
    }
}
