<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Hook;

use Drupal\example\HookMethods\AbstractClassWithHookMethod;
use Drupal\example\HookMethods\ClassInheritingHookMethod;
use Drupal\example\HookMethods\ClassLevelHookNamingMethod;
use Drupal\example\HookMethods\ClassLevelHookNamingMethodPositionally;
use Drupal\example\HookMethods\ClassLevelHookOnInvoke;
use Drupal\example\HookMethods\MethodWithHuxAlterAttribute;
use Drupal\example\HookMethods\MethodWithHuxHookAttribute;
use Drupal\example\HookMethods\MethodWithHuxReplaceOriginalHookAttribute;
use Drupal\example\HookMethods\ProtectedMethodWithHookAttribute;
use Drupal\example\HookMethods\PublicMethodWithHookAttribute;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Reload\DeadCodeDetectorDrupal\Core\Hook\HookMethods;

use function array_keys;

final class HookMethodsTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, list<string>}>
     */
    public static function hookMethods(): iterable
    {
        yield 'public method with a hook attribute, and no other'
            => [PublicMethodWithHookAttribute::class, ['hookMethod']];
        yield 'none for a protected method, which core ignores' => [ProtectedMethodWithHookAttribute::class, []];
        yield 'method a class-level hook names'
            => [ClassLevelHookNamingMethod::class, ['methodNamedByClassLevelHook']];
        yield 'method a class-level hook names positionally'
            => [ClassLevelHookNamingMethodPositionally::class, ['methodNamedPositionally']];
        yield '__invoke() for a class-level hook naming no method' => [ClassLevelHookOnInvoke::class, ['__invoke']];
        yield 'inherited hook method' => [ClassInheritingHookMethod::class, ['inheritedHookMethod']];
        yield 'none for an abstract class' => [AbstractClassWithHookMethod::class, []];
        yield 'method with Hux hook attribute' => [MethodWithHuxHookAttribute::class, ['huxHookMethod']];
        yield 'method with Hux alter attribute' => [MethodWithHuxAlterAttribute::class, ['huxAlterMethod']];
        yield 'method replacing a hook through Hux'
            => [MethodWithHuxReplaceOriginalHookAttribute::class, ['methodReplacingOriginalHook']];
    }

    /**
     * @param class-string $className
     * @param list<string> $expectedMethodNames
     */
    #[DataProvider('hookMethods')]
    public function testHookMethodsAreFound(string $className, array $expectedMethodNames): void
    {
        self::assertSame($expectedMethodNames, array_keys(HookMethods::of(new ReflectionClass($className))));
    }

    public function testHookMethodIsPairedWithTheHookItImplements(): void
    {
        self::assertSame(
            ['hookMethod' => 'hook_form_alter'],
            HookMethods::of(new ReflectionClass(PublicMethodWithHookAttribute::class)),
        );
    }
}
