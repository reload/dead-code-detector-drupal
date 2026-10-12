<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Reflection;

use ReflectionProperty;

/**
 * Properties that a base class's code can access by name on a subtype.
 */
final class ReachableProperties
{
    /**
     * Non-static and non-private: the base class's code runs in its own scope,
     * which cannot see a subtype's private properties.
     *
     * @phpstan-pure
     */
    public static function includes(ReflectionProperty $property): bool
    {
        return !$property->isStatic() && !$property->isPrivate();
    }
}
