<?php

declare(strict_types=1);

namespace Drupal\example\HookMethods;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook as HuxHook;
use Drupal\hux\Attribute\ReplaceOriginalHook;

final class PublicMethodWithHookAttribute
{
    #[Hook('form_alter')]
    public function hookMethod(): void
    {
    }

    public function methodWithoutHookAttribute(): void
    {
    }

    #[\ReturnTypeWillChange]
    public function methodWithOtherAttribute(): void
    {
    }
}

final class ProtectedMethodWithHookAttribute
{
    #[Hook('form_alter')]
    protected function protectedHookMethod(): void
    {
    }
}

#[Hook('node_presave', method: 'methodNamedByClassLevelHook')]
final class ClassLevelHookNamingMethod
{
    public function methodNamedByClassLevelHook(): void
    {
    }
}

#[Hook('node_insert', 'methodNamedPositionally')]
final class ClassLevelHookNamingMethodPositionally
{
    public function methodNamedPositionally(): void
    {
    }
}

#[Hook('cron')]
final class ClassLevelHookOnInvoke
{
    public function __invoke(): void
    {
    }
}

abstract class BaseDeclaringHookMethod
{
    #[Hook('node_insert')]
    public function inheritedHookMethod(): void
    {
    }
}

final class ClassInheritingHookMethod extends BaseDeclaringHookMethod
{
}

abstract class AbstractClassWithHookMethod
{
    #[Hook('cron')]
    public function hookMethodOfAbstractClass(): void
    {
    }
}

final class MethodWithHuxHookAttribute
{
    #[HuxHook('node_presave')]
    public function huxHookMethod(): void
    {
    }
}

final class MethodWithHuxAlterAttribute
{
    #[Alter('mail')]
    public function huxAlterMethod(): void
    {
    }
}

final class MethodWithHuxReplaceOriginalHookAttribute
{
    #[ReplaceOriginalHook('node_insert', 'node')]
    public function methodReplacingOriginalHook(): void
    {
    }
}
