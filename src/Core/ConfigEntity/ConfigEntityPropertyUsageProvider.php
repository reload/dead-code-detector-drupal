<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\ConfigEntity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\PropertiesAccessedByNameUsageProvider;
use ShipMonk\PHPStan\DeadCode\Enum\AccessType;

/**
 * Config entity properties, which the constructor sets from stored config and
 * toArray() exports, both by name.
 *
 * @see \Drupal\Core\Entity\EntityBase::__construct()
 * @see \Drupal\Core\Config\Entity\ConfigEntityBase::toArray()
 * @see https://www.drupal.org/docs/drupal-apis/configuration-api/creating-a-configuration-entity-type
 */
final class ConfigEntityPropertyUsageProvider extends PropertiesAccessedByNameUsageProvider
{
    protected function typeName(): string
    {
        return ConfigEntityBase::class;
    }

    protected function accessTypes(): array
    {
        return [AccessType::READ, AccessType::WRITE];
    }

    protected function note(): string
    {
        return 'Config entity property, set from and exported to config';
    }
}
