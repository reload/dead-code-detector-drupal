<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Plugin;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\PropertiesAccessedByNameUsageProvider;
use ShipMonk\PHPStan\DeadCode\Enum\AccessType;

/**
 * Plugin attribute properties, which AttributeBase::get() returns as the
 * plugin definition.
 *
 * @see \Drupal\Component\Plugin\Attribute\AttributeBase::get()
 * @see https://www.drupal.org/docs/drupal-apis/plugin-api/attribute-based-plugins
 */
final class PluginAttributePropertyUsageProvider extends PropertiesAccessedByNameUsageProvider
{
    protected function typeName(): string
    {
        return AttributeBase::class;
    }

    protected function accessTypes(): array
    {
        return [AccessType::READ];
    }

    protected function note(): string
    {
        return 'Plugin definition, read by AttributeBase::get()';
    }
}
