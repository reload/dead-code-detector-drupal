<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Contrib\Drush;

use ReflectionClass;
use ReflectionMethod;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\ClassAncestry;

use function in_array;
use function strtolower;

/**
 * Command classes Drush instantiates, and the factories it builds them with.
 *
 * @see https://www.drush.org/13.x/dependency-injection/
 */
final class DrushCommandClasses
{
    private const BASE_CLASS = 'Drush\Commands\DrushCommands';

    /**
     * Whether Drush instantiates the class as a command class.
     *
     * @param ReflectionClass<*> $class
     * @phpstan-pure
     */
    public static function isCommandClass(ReflectionClass $class): bool
    {
        return in_array(self::BASE_CLASS, ClassAncestry::typeNames($class), true);
    }

    /**
     * Drush builds a command class through its static create() or
     * createEarly(), if it has one.
     *
     * @phpstan-pure
     */
    public static function isFactory(ReflectionMethod $method): bool
    {
        return $method->isStatic()
            && $method->isPublic()
            && in_array(strtolower($method->getName()), ['create', 'createearly'], true)
            && self::isCommandClass($method->getDeclaringClass());
    }
}
