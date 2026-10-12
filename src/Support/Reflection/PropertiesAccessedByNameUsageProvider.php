<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Reflection;

use ReflectionClass;
use ReflectionProperty;
use ShipMonk\PHPStan\DeadCode\Enum\AccessType;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

use function in_array;

/**
 * Properties that library code reads or writes by name on subtypes of a type.
 *
 * A trait's property is referenced through the class using the trait, which
 * dead-code-detector resolves to the trait.
 */
abstract class PropertiesAccessedByNameUsageProvider extends ReflectionBasedMemberUsageProvider
{
    /**
     * @var array<string, bool>
     *   Class name => whether library code accesses its properties.
     */
    private array $accessesPropertiesByClassName = [];

    /**
     * The class, interface or trait whose subtypes have the properties.
     */
    abstract protected function typeName(): string;

    /**
     * @return list<AccessType::*>
     */
    abstract protected function accessTypes(): array;

    /**
     * Why the properties are accessed, for DCD's output.
     */
    abstract protected function note(): string;

    /**
     * Whether library code accesses the properties of this subtype.
     *
     * @param ReflectionClass<*> $class
     */
    protected function accessesPropertiesOf(ReflectionClass $class): bool
    {
        return true;
    }

    protected function shouldMarkPropertyAsRead(ReflectionProperty $property): ?VirtualUsageData
    {
        return $this->propertyUsage($property, AccessType::READ);
    }

    protected function shouldMarkPropertyAsWritten(ReflectionProperty $property): ?VirtualUsageData
    {
        return $this->propertyUsage($property, AccessType::WRITE);
    }

    private function propertyUsage(ReflectionProperty $property, AccessType $accessType): ?VirtualUsageData
    {
        if (!in_array($accessType, $this->accessTypes(), true) || !ReachableProperties::includes($property)) {
            return null;
        }

        // Asked per property and access type; the answer is the class's.
        $class = $property->getDeclaringClass();
        $accessesProperties = $this->accessesPropertiesByClassName[$class->getName()] ??=
            in_array($this->typeName(), ClassAncestry::typeNames($class), true)
            && $this->accessesPropertiesOf($class);

        return $accessesProperties ? VirtualUsageData::withNote($this->note()) : null;
    }
}
