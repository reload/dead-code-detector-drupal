<?php

declare(strict_types=1);

namespace Drupal\example\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Entity\ConfigEntityBase;

final class ConfigEntityWithActionMethods extends ConfigEntityBase
{
    #[ActionMethod]
    public function publicActionMethod(): void
    {
    }

    #[ActionMethod]
    protected function protectedMethodWithActionMethodAttribute(): void
    {
    }

    public function publicMethodWithoutActionMethodAttribute(): void
    {
    }
}

abstract class BaseConfigEntityWithActionMethod extends ConfigEntityBase
{
    #[ActionMethod]
    public function actionMethodInheritedByConfigEntity(): void
    {
    }
}

final class ConfigEntityInheritingActionMethod extends BaseConfigEntityWithActionMethod
{
}

abstract class ConfigEntityBaseDeclaringActionMethod extends ConfigEntityBase
{
    #[ActionMethod]
    public function actionMethodOverriddenWithoutAttribute(): void
    {
    }
}

final class ConfigEntityOverridingActionMethod extends ConfigEntityBaseDeclaringActionMethod
{
    public function actionMethodOverriddenWithoutAttribute(): void
    {
    }
}

abstract class AbstractConfigEntityNothingExtends extends ConfigEntityBase
{
    #[ActionMethod]
    public function actionMethodOfAbstractConfigEntity(): void
    {
    }
}

final class ActionMethodOutsideConfigEntity
{
    #[ActionMethod]
    public function publicActionMethod(): void
    {
    }
}
