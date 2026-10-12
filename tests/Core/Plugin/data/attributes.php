<?php

declare(strict_types=1);

namespace Drupal\example\Plugin;

use Attribute;
use Drupal\Component\Plugin\Attribute\AttributeBase;

#[Attribute(Attribute::TARGET_CLASS)]
final class PluginAttributeReadAsDefinition extends AttributeBase
{
    public string $readThroughDefinition = '';

    // No default: a default value counts as a write.
    public string $readButNeverWritten;
}

#[Attribute(Attribute::TARGET_CLASS)]
final class AttributeOfNoPlugin
{
    public string $propertyNothingReads = '';
}
