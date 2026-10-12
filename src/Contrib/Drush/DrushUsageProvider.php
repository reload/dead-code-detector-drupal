<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Contrib\Drush;

use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

use function in_array;
use function is_string;
use function preg_match;

/**
 * Drush commands and hooks, and the command classes Drush instantiates.
 *
 * @see https://www.drush.org/13.x/commands/
 * @see https://www.drush.org/13.x/hooks/
 */
final class DrushUsageProvider extends ReflectionBasedMemberUsageProvider
{
    private const COMMAND_ATTRIBUTES = ['Drush\Attributes\Command', 'Drush\Attributes\Hook'];

    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        foreach ($method->getAttributes() as $attribute) {
            if (in_array($attribute->getName(), self::COMMAND_ATTRIBUTES, true)) {
                return VirtualUsageData::withNote('Drush command or hook (' . $attribute->getName() . ')');
            }
        }

        // Annotated commands, from before Drush 12's attributes.
        $docComment = $method->getDocComment();
        if (is_string($docComment) && preg_match('/^\s*\*\s*@(command|hook)\b/m', $docComment) === 1) {
            return VirtualUsageData::withNote('Drush command or hook (annotation)');
        }

        if (DrushCommandClasses::isFactory($method)) {
            return VirtualUsageData::withNote('Drush command class factory, called by Drush');
        }

        // Drush instantiates command classes, through create() or drush.services.yml.
        if ($method->isConstructor() && DrushCommandClasses::isCommandClass($method->getDeclaringClass())) {
            return VirtualUsageData::withNote('Drush command class, instantiated by Drush');
        }

        return null;
    }
}
