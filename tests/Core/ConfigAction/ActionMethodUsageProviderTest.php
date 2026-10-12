<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigAction;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class ActionMethodUsageProviderTest extends DeadCodeRuleTestCase
{
    private const ENTITY_NAMESPACE = 'Drupal\example\Entity';

    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/config.neon'];
    }

    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/config-entities.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'public action method of a config entity'
            => [self::ENTITY_NAMESPACE . '\ConfigEntityWithActionMethods::publicActionMethod'];
        yield 'action method a config entity inherits' => [
            self::ENTITY_NAMESPACE
                . '\BaseConfigEntityWithActionMethod::actionMethodInheritedByConfigEntity',
        ];
        yield 'override of an action method, without the attribute' => [
            self::ENTITY_NAMESPACE . '\ConfigEntityOverridingActionMethod::actionMethodOverriddenWithoutAttribute',
        ];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'protected method with the attribute' => [
            'Unused ' . self::ENTITY_NAMESPACE
                . '\ConfigEntityWithActionMethods::protectedMethodWithActionMethodAttribute',
        ];
        yield 'public method without the attribute' => [
            'Unused ' . self::ENTITY_NAMESPACE
                . '\ConfigEntityWithActionMethods::publicMethodWithoutActionMethodAttribute',
        ];
        yield 'overridden declaration of an action method' => [
            'Unused ' . self::ENTITY_NAMESPACE
                . '\ConfigEntityBaseDeclaringActionMethod::actionMethodOverriddenWithoutAttribute',
        ];
        yield 'action method of an abstract config entity nothing extends' => [
            'Unused ' . self::ENTITY_NAMESPACE
                . '\AbstractConfigEntityNothingExtends::actionMethodOfAbstractConfigEntity',
        ];
        yield 'action method outside a config entity'
            => ['Unused ' . self::ENTITY_NAMESPACE . '\ActionMethodOutsideConfigEntity::publicActionMethod'];
    }
}
