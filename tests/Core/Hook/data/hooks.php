<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;

final class MethodLevelHooks
{
    public function __construct()
    {
    }

    #[Hook('form_alter')]
    public function methodWithHookAttribute(): void
    {
    }

    public function methodNoHookNames(): void
    {
    }
}

final class ClassWithoutHooks
{
    public function __construct()
    {
    }
}

abstract class BaseOfHookClassDeclaringConstructor
{
    public function __construct()
    {
    }
}

final class HookClassInheritingConstructor extends BaseOfHookClassDeclaringConstructor
{
    #[Hook('cron')]
    public function cron(): void
    {
    }
}

abstract class BaseDeclaringClassLevelHookTarget
{
    public function run(): void
    {
    }
}

#[Hook('cron', method: 'run')]
final class ClassLevelHookTargetingInheritedMethod extends BaseDeclaringClassLevelHookTarget
{
}

abstract class BaseDeclaringHookMethod
{
    #[Hook('node_insert')]
    public function inheritedHookMethod(): void
    {
    }
}

final class HookClassInheritingHookMethods extends BaseDeclaringHookMethod
{
    public function __construct()
    {
    }
}
