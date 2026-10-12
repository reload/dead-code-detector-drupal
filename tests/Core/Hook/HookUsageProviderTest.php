<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Hook;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class HookUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/hooks.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'method with hook attribute' => ['Drupal\example\Hook\MethodLevelHooks::methodWithHookAttribute'];
        yield 'constructor of class with method hooks' => ['Drupal\example\Hook\MethodLevelHooks::__construct'];
        yield 'constructor a hook class inherits'
            => ['Drupal\example\Hook\BaseOfHookClassDeclaringConstructor::__construct'];
        yield 'inherited method a class-level hook names'
            => ['Drupal\example\Hook\BaseDeclaringClassLevelHookTarget::run'];
        yield 'hook method a class inherits' => ['Drupal\example\Hook\BaseDeclaringHookMethod::inheritedHookMethod'];
        yield 'constructor of class whose hook methods are inherited'
            => ['Drupal\example\Hook\HookClassInheritingHookMethods::__construct'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'method without hook attribute' => ['Unused Drupal\example\Hook\MethodLevelHooks::methodNoHookNames'];
        yield 'constructor of class without hooks' => ['Unused Drupal\example\Hook\ClassWithoutHooks::__construct'];
    }
}
