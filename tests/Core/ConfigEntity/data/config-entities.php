<?php

declare(strict_types=1);

namespace Drupal\example\ConfigEntity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

final class ConfigEntityAccessedByName extends ConfigEntityBase
{
    // No default: a default value counts as a write.
    protected string $propertyFromConfiguration;

    private string $privatePropertyConfigCannotReach = '';
}

trait InnerTraitWithConfigEntityProperty
{
    protected string $propertyFromInnerTrait;
}

trait TraitWithConfigEntityProperty
{
    use InnerTraitWithConfigEntityProperty;

    protected string $propertyFromTrait;
}

final class ConfigEntityUsingTraitWithProperty extends ConfigEntityBase
{
    use TraitWithConfigEntityProperty;
}

final class NotAConfigEntity
{
    protected string $propertyNothingReads = '';
}
