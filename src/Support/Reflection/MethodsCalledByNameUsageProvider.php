<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Reflection;

use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

use function in_array;
use function strtolower;

/**
 * Public methods that library code calls by name on subtypes of a type that
 * does not declare them.
 */
abstract class MethodsCalledByNameUsageProvider extends ReflectionBasedMemberUsageProvider
{
    /**
     * The class or interface whose subtypes have the methods.
     */
    abstract protected function typeName(): string;

    /**
     * The methods called by name.
     *
     * @return list<string>
     *   Lowercased, as PHP method names are case-insensitive.
     */
    abstract protected function methodNames(): array;

    /**
     * Why the methods are called, for DCD's output.
     */
    abstract protected function note(): string;

    /**
     * Used when public, named in methodNames() and declared on a subtype of
     * typeName().
     */
    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        if (
            !$method->isPublic()
            || !in_array(strtolower($method->getName()), $this->methodNames(), true)
            || !in_array($this->typeName(), ClassAncestry::typeNames($method->getDeclaringClass()), true)
        ) {
            return null;
        }

        return VirtualUsageData::withNote($this->note());
    }
}
