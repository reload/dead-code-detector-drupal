<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Hook;

use ReflectionClass;
use ReflectionMethod;

use function is_string;

/**
 * Hook implementations as HookCollectorPass, and Hux's discovery, find them:
 * public methods with hook attributes, inherited ones included, and the
 * methods that class-level hooks name.
 *
 * @see \Drupal\Core\Hook\HookCollectorPass
 */
final class HookMethods
{
    /**
     * The methods Drupal calls as hooks, with the hook each implements.
     *
     * @param ReflectionClass<*> $class
     * @return array<string, string>
     *   Method name => hook it implements.
     * @phpstan-pure
     */
    public static function of(ReflectionClass $class): array
    {
        // The container cannot instantiate an abstract class.
        if ($class->isAbstract()) {
            return [];
        }

        $hookNamesByMethod = [];
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes() as $attribute) {
                if (HookAttributes::isMethodHook($attribute->getName())) {
                    $hookNamesByMethod[$method->getName()]
                        = HookAttributes::hookName($attribute->getName(), $attribute->getArguments());
                }
            }
        }

        foreach ($class->getAttributes() as $attribute) {
            if (HookAttributes::isClassHook($attribute->getName())) {
                $methodName = $attribute->getArguments()['method'] ?? $attribute->getArguments()[1] ?? '';
                $methodName = is_string($methodName) && $methodName !== '' ? $methodName : '__invoke';
                $hookNamesByMethod[$methodName]
                    = HookAttributes::hookName($attribute->getName(), $attribute->getArguments());
            }
        }

        return $hookNamesByMethod;
    }
}
