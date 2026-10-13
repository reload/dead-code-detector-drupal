<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Hook;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use ReflectionClass;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

/**
 * OOP hook implementations and their classes, which Drupal, or Hux,
 * registers as autowired services.
 *
 * @see https://www.drupal.org/docs/develop/creating-modules/understanding-hooks
 * @see https://www.drupal.org/node/3442349
 */
final class HookUsageProvider implements MemberUsageProvider
{
    /**
     * The class's hook implementations, and its constructor when it has any.
     */
    public function getUsages(Node $node, Scope $scope): array
    {
        // @phpstan-ignore phpstanApi.instanceofAssumption
        if (!$node instanceof InClassNode) {
            return [];
        }

        $class = $node->getClassReflection()->getNativeReflection();

        $usages = [];
        foreach (HookMethods::of($class) as $methodName => $hookName) {
            $usages[] = $this->usage($class, $methodName, 'Implements ' . $hookName);
        }

        if ($usages !== [] && $class->hasMethod('__construct')) {
            $usages[] = $this->usage($class, '__construct', 'Hook class, constructed by the service container');
        }

        return $usages;
    }

    /**
     * The class's own name, so an inherited method counts too.
     *
     * @param ReflectionClass<*> $class
     */
    private function usage(ReflectionClass $class, string $methodName, string $note): ClassMethodUsage
    {
        return new ClassMethodUsage(
            UsageOrigin::createVirtual($this, VirtualUsageData::withNote($note)),
            new ClassMethodRef($class->getName(), $methodName, possibleDescendant: false),
        );
    }
}
