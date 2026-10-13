<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Reflection;

use ReflectionClass;

use function array_keys;

/**
 * Type checks by name, for types that may not be installed.
 */
final class ClassAncestry
{
    /**
     * Names of the class's parents, interfaces and traits, including traits of
     * traits and of parents.
     *
     * Names rather than reflection of those types, so checking for a type from
     * a module or tool that is not installed costs nothing.
     *
     * @param ReflectionClass<*> $class
     * @return list<string>
     * @phpstan-pure
     */
    public static function typeNames(ReflectionClass $class): array
    {
        $typeNames = [];
        foreach ($class->getInterfaceNames() as $interfaceName) {
            $typeNames[$interfaceName] = true;
        }

        for ($current = $class; $current !== false; $current = $current->getParentClass()) {
            if ($current !== $class) {
                $typeNames[$current->getName()] = true;
            }
            $typeNames += self::traitNames($current);
        }

        return array_keys($typeNames);
    }

    /**
     * The class's traits and their traits, recursively.
     *
     * @param ReflectionClass<*> $class
     * @return array<string, true>
     * @phpstan-pure
     */
    private static function traitNames(ReflectionClass $class): array
    {
        $traitNames = [];
        foreach ($class->getTraits() as $trait) {
            $traitNames[$trait->getName()] = true;
            $traitNames += self::traitNames($trait);
        }

        return $traitNames;
    }
}
