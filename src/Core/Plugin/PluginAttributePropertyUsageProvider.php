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
    /**
     * The base class whose get() returns the properties.
     */
    protected function typeName(): string
    {
        return AttributeBase::class;
    }

    /**
     * Read only: the attribute's constructor writes them.
     */
    protected function accessTypes(): array
    {
        return [AccessType::READ];
    }

    /**
     * Names AttributeBase::get() as the reader.
     */
    protected function note(): string
    {
        return 'Plugin definition, read by AttributeBase::get()';
    }
}
