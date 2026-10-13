<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ActionMethod;
use ReflectionClass;
use ReflectionMethod;

use function array_values;

/**
 * Which methods of a config entity recipes can call through config actions,
 * mirroring EntityMethodDeriver.
 *
 * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver\EntityMethodDeriver
 */
final class ActionMethods
{
    /**
     * Each class up the hierarchy: a parent's attribute also makes Drupal
     * call an override that lacks it. Each name once, though every class
     * lists the public methods it inherits.
     *
     * @param ReflectionClass<*> $class
     * @return list<string>
     * @phpstan-pure
     */
    public static function methodNames(ReflectionClass $class): array
    {
        $methodNames = [];
        for ($current = $class; $current !== false; $current = $current->getParentClass()) {
            foreach ($current->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (self::hasActionMethodAttribute($method)) {
                    $methodNames[$method->getName()] = $method->getName();
                }
            }
        }

        return array_values($methodNames);
    }

    /**
     * @phpstan-pure
     */
    private static function hasActionMethodAttribute(ReflectionMethod $method): bool
    {
        foreach ($method->getAttributes() as $attribute) {
            if ($attribute->getName() === ActionMethod::class) {
                return true;
            }
        }

        return false;
    }
}
