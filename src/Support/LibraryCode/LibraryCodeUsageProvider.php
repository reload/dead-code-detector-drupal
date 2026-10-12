<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\LibraryCode;

use PhpParser\Node;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionEnumUnitCase;
use ReflectionMethod;
use ReflectionProperty;
use ShipMonk\PHPStan\DeadCode\Enum\AccessType;
use ShipMonk\PHPStan\DeadCode\Graph\ClassConstantRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassConstantUsage;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMemberUsage;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\ClassPropertyRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassPropertyUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

use function sprintf;

/**
 * Members of Composer-installed code, and members overriding or implementing
 * them, are used by that code, so analysed library code is never reported.
 *
 * Generalises dead-code-detector's VendorUsageProvider, which only knows the vendor
 * directory, to every Composer install path: Drupal core and contrib live
 * outside vendor/.
 *
 * @see \ShipMonk\PHPStan\DeadCode\Provider\VendorUsageProvider
 */
final class LibraryCodeUsageProvider extends ReflectionBasedMemberUsageProvider
{
    public function __construct(
        private readonly LibraryPaths $libraryPaths,
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getUsages(Node $node, Scope $scope): array
    {
        // The base sees a trait only as part of the classes using it, so a
        // library trait that no analysed class uses needs its own visit.
        if ($node instanceof Trait_ && $node->namespacedName !== null) {
            $traitName = $node->namespacedName->toString();

            return $this->libraryTraitUsages($this->reflectionProvider->getClass($traitName)->getNativeReflection());
        }

        return parent::getUsages($node, $scope);
    }

    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        return $this->findLibraryUsage($method);
    }

    protected function shouldMarkConstantAsUsed(ReflectionClassConstant $constant): ?VirtualUsageData
    {
        return $this->findLibraryUsage($constant);
    }

    protected function shouldMarkEnumCaseAsUsed(ReflectionEnumUnitCase $enumCase): ?VirtualUsageData
    {
        return $this->findLibraryUsage($enumCase);
    }

    protected function shouldMarkPropertyAsRead(ReflectionProperty $property): ?VirtualUsageData
    {
        return $this->findLibraryUsage($property);
    }

    protected function shouldMarkPropertyAsWritten(ReflectionProperty $property): ?VirtualUsageData
    {
        return $this->findLibraryUsage($property);
    }

    private function findLibraryUsage(
        ReflectionMethod|ReflectionClassConstant|ReflectionProperty $member,
    ): ?VirtualUsageData {
        $class = $member->getDeclaringClass();

        do {
            foreach ([$class, ...$class->getInterfaces(), ...$class->getTraits()] as $ancestor) {
                $packageName = $this->findLibraryPackageDeclaring($ancestor, $member);
                if ($packageName !== null) {
                    return VirtualUsageData::withNote(sprintf(
                        'Overrides %s::%s from Composer package %s, which is expected to use it',
                        $ancestor->getName(),
                        $member->getName(),
                        $packageName,
                    ));
                }
            }

            $class = $class->getParentClass();
        } while ($class !== false);

        return null;
    }

    /**
     * @param ReflectionClass<object> $ancestor
     */
    private function findLibraryPackageDeclaring(
        ReflectionClass $ancestor,
        ReflectionMethod|ReflectionClassConstant|ReflectionProperty $member,
    ): ?string {
        $hasMember = match (true) {
            $member instanceof ReflectionMethod => $ancestor->hasMethod($member->getName()),
            $member instanceof ReflectionClassConstant => $ancestor->hasConstant($member->getName()),
            $member instanceof ReflectionProperty => $ancestor->hasProperty($member->getName()),
        };

        // Built-in classes have PHPStan stubs; dead-code-detector's BuiltinUsageProvider covers them.
        if (!$hasMember || $ancestor->getExtensionName() !== false) {
            return null;
        }

        return $this->packageOf($ancestor);
    }

    /**
     * @param ReflectionClass<*> $class
     */
    private function packageOf(ReflectionClass $class): ?string
    {
        $fileName = $class->getFileName();

        return $fileName === false ? null : $this->libraryPaths->findPackageName($fileName);
    }

    /**
     * @param ReflectionClass<*> $trait
     * @return list<ClassMemberUsage>
     */
    private function libraryTraitUsages(ReflectionClass $trait): array
    {
        $packageName = $this->packageOf($trait);
        if ($packageName === null) {
            return [];
        }

        $origin = UsageOrigin::createVirtual($this, VirtualUsageData::withNote(
            sprintf('Member of %s from Composer package %s', $trait->getName(), $packageName),
        ));
        $usages = [];
        foreach ($trait->getMethods() as $method) {
            $usages[] = new ClassMethodUsage(
                $origin,
                new ClassMethodRef($trait->getName(), $method->getName(), possibleDescendant: false),
            );
        }

        foreach ($trait->getReflectionConstants() as $constant) {
            $usages[] = new ClassConstantUsage(
                $origin,
                new ClassConstantRef(
                    $trait->getName(),
                    $constant->getName(),
                    possibleDescendant: false,
                    isEnumCase: TrinaryLogic::createNo(),
                ),
            );
        }

        foreach ($trait->getProperties() as $property) {
            foreach ([AccessType::READ, AccessType::WRITE] as $accessType) {
                $usages[] = new ClassPropertyUsage(
                    $origin,
                    new ClassPropertyRef($trait->getName(), $property->getName(), possibleDescendant: false),
                    $accessType,
                );
            }
        }

        return $usages;
    }
}
