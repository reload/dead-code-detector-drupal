<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Plugin;

use Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection\ByNameRuleTestCase;

final class PluginAttributePropertyUsageProviderTest extends ByNameRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/attributes.php'];
    }

    protected static function fixtureNamespace(): string
    {
        return 'Drupal\example\Plugin';
    }

    public static function usedMembers(): iterable
    {
        yield 'plugin attribute property'
            => ['Drupal\example\Plugin\PluginAttributeReadAsDefinition::$readThroughDefinition'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'plugin attribute property nothing writes, as get() only reads'
            => ['Property Drupal\example\Plugin\PluginAttributeReadAsDefinition::$readButNeverWritten'
                . ' is never written'];
        yield 'property of an attribute that is no plugin attribute'
            => ['Property Drupal\example\Plugin\AttributeOfNoPlugin::$propertyNothingReads is never read'];
    }
}
