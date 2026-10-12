<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Routing;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

use function array_map;

/**
 * Constructors of controllers whose routes core finds through #[Route].
 *
 * The class resolver builds them, often through AutowireTrait::create(), which
 * DCD does not see. DCD's Symfony support marks the routed methods.
 *
 * @see https://www.drupal.org/node/3324758
 */
final class RouteAttributeUsageProvider implements MemberUsageProvider
{
    public function getUsages(Node $node, Scope $scope): array
    {
        // @phpstan-ignore phpstanApi.instanceofAssumption
        if (!$node instanceof InClassNode) {
            return [];
        }

        $class = $node->getClassReflection()->getNativeReflection();
        if (!self::declaresRoutes($class)) {
            return [];
        }

        // The class's own name, so an inherited constructor counts too.
        return [
            new ClassMethodUsage(
                UsageOrigin::createVirtual(
                    $this,
                    VirtualUsageData::withNote('Controller of #[Route] routes, built by the class resolver'),
                ),
                new ClassMethodRef($class->getName(), '__construct', possibleDescendant: false),
            ),
        ];
    }

    /**
     * @param ReflectionClass<*> $class
     */
    private static function declaresRoutes(ReflectionClass $class): bool
    {
        // Inherited methods too: core reads every method of the class.
        $methodAttributeNames = array_map(
            static fn (ReflectionMethod $method): array => self::names($method->getAttributes()),
            $class->getMethods(),
        );

        return RouteAttributes::declaresRoutes(
            $class->getName(),
            $class->isAbstract(),
            $class->hasMethod('__invoke'),
            self::names($class->getAttributes()),
            $methodAttributeNames,
        );
    }

    /**
     * @param list<ReflectionAttribute<object>> $attributes
     * @return list<string>
     */
    private static function names(array $attributes): array
    {
        return array_map(static fn (ReflectionAttribute $attribute): string => $attribute->getName(), $attributes);
    }
}
