<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigEntity;

use Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection\ByNameRuleTestCase;

final class ConfigEntityPropertyUsageProviderTest extends ByNameRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/config-entities.php'];
    }

    protected static function fixtureNamespace(): string
    {
        return 'Drupal\example\ConfigEntity';
    }

    public static function usedMembers(): iterable
    {
        yield 'config entity property'
            => ['Drupal\example\ConfigEntity\ConfigEntityAccessedByName::$propertyFromConfiguration'];
        yield 'config entity property declared in a trait, which shipmonk reports on the trait'
            => ['Drupal\example\ConfigEntity\TraitWithConfigEntityProperty::$propertyFromTrait'];
        yield 'config entity property declared in a trait\'s trait'
            => ['Drupal\example\ConfigEntity\InnerTraitWithConfigEntityProperty::$propertyFromInnerTrait'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'property of a class that is no config entity'
            => ['Property Drupal\example\ConfigEntity\NotAConfigEntity::$propertyNothingReads is never read'];
        yield 'private property of a config entity'
            => ['Property Drupal\example\ConfigEntity\ConfigEntityAccessedByName::$privatePropertyConfigCannotReach'
                . ' is never read'];
    }
}
