<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\ConfigAction;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
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
    /**
     * The action methods of a concrete config entity class, its parents'
     * included.
     */
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
        foreach (ActionMethods::methodNames($class) as $methodName) {
            // The class's own name, so the method it inherits or overrides counts.
            $usages[] = new ClassMethodUsage(
                $origin,
                new ClassMethodRef($class->getName(), $methodName, possibleDescendant: false),
            );
        }

        return $usages;
    }
}
