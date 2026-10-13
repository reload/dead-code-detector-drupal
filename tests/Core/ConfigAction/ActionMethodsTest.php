<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigAction;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Reload\DeadCodeDetectorDrupal\Core\ConfigAction\ActionMethods;
use Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigAction\ActionMethods\ChildInheritingActionMethod;
use Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigAction\ActionMethods\ChildOverridingActionMethod;
use Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigAction\ActionMethods\ClassWithProtectedActionMethod;
use Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigAction\ActionMethods\ClassWithPublicMethodWithOtherAttribute;
use Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigAction\ActionMethods\ClassWithPublicMethodWithoutAttributes;
use Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigAction\ActionMethods\ClassWithTwoPublicActionMethods;

final class ActionMethodsTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, list<string>}>
     */
    public static function classes(): iterable
    {
        yield 'public methods with the attribute'
            => [ClassWithTwoPublicActionMethods::class, ['firstActionMethod', 'secondActionMethod']];
        yield 'inherited method with the attribute, named once'
            => [ChildInheritingActionMethod::class, ['inheritedActionMethod']];
        yield 'override, without the attribute, of a method with it'
            => [ChildOverridingActionMethod::class, ['actionMethodOverriddenWithoutAttribute']];
        yield 'protected method with the attribute' => [ClassWithProtectedActionMethod::class, []];
        yield 'public method without attributes' => [ClassWithPublicMethodWithoutAttributes::class, []];
        yield 'public method with another attribute' => [ClassWithPublicMethodWithOtherAttribute::class, []];
    }

    /**
     * @param class-string $className
     * @param list<string> $expectedMethodNames
     */
    #[DataProvider('classes')]
    public function testActionMethodNamesOfClass(string $className, array $expectedMethodNames): void
    {
        self::assertSame($expectedMethodNames, ActionMethods::methodNames(new ReflectionClass($className)));
    }
}
