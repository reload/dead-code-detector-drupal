<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\ConfigAction;

use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use ReflectionClass;
use ReflectionMethod;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\ClassAncestry;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

use function in_array;

/**
 * Config entity methods with #[ActionMethod], which recipes can call through
 * config actions (Drupal 10.3+).
 *
 * @see \Drupal\Core\Config\Action\Attribute\ActionMethod
 * @see \Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver\EntityMethodDeriver
 */
final class ActionMethodUsageProvider implements MemberUsageProvider
{
    public function getUsages(Node $node, Scope $scope): array
    {
        // @phpstan-ignore phpstanApi.instanceofAssumption
        if (!$node instanceof InClassNode) {
            return [];
        }

        // Entity types are concrete classes; abstract ones are reached as parents.
        $class = $node->getClassReflection()->getNativeReflection();
        if ($class->isAbstract() || !in_array(ConfigEntityInterface::class, ClassAncestry::typeNames($class), true)) {
            return [];
        }

        $origin = UsageOrigin::createVirtual(
            $this,
            VirtualUsageData::withNote('Config action method, which recipes can call'),
        );
        $usages = [];
        foreach (self::actionMethodNames($class) as $methodName) {
            // The class's own name, so the method it inherits or overrides counts.
            $usages[] = new ClassMethodUsage(
                $origin,
                new ClassMethodRef($class->getName(), $methodName, possibleDescendant: false),
            );
        }

        return $usages;
    }

    /**
     * Like EntityMethodDeriver, each class up the hierarchy: a parent's
     * attribute also makes Drupal call an override that lacks it.
     *
     * @param ReflectionClass<*> $class
     * @return list<string>
     */
    private static function actionMethodNames(ReflectionClass $class): array
    {
        $methodNames = [];
        for ($current = $class; $current !== false; $current = $current->getParentClass()) {
            foreach ($current->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (self::hasActionMethodAttribute($method)) {
                    $methodNames[] = $method->getName();
                }
            }
        }

        return $methodNames;
    }

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
