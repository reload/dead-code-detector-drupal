<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Routing;

use Symfony\Component\Routing\Attribute\Route;

use function array_intersect;
use function explode;

/**
 * Which classes core's attribute route discovery (Drupal 11.4+) finds routes
 * on, mirroring AttributeRouteDiscovery.
 *
 * @see \Drupal\Core\Routing\AttributeRouteDiscovery
 * @see https://www.drupal.org/node/3324758
 */
final class RouteAttributes
{
    private const ROUTE_ATTRIBUTES = [
        Route::class,
        // Deprecated subclass, which core's IS_INSTANCEOF lookup also finds. A
        // string, as Symfony 8 removes it.
        'Symfony\Component\Routing\Annotation\Route',
    ];

    /**
     * Whether discovery finds routes on the class: it reads only concrete
     * classes in a module's Controller namespace.
     *
     * @param list<string> $classAttributeNames
     * @param list<list<string>> $methodAttributeNames
     *   Attribute names of each method, inherited methods included.
     * @phpstan-pure
     */
    public static function declaresRoutes(
        string $className,
        bool $isAbstract,
        bool $isInvokable,
        array $classAttributeNames,
        array $methodAttributeNames,
    ): bool {
        $namespaceParts = explode('\\', $className);
        $isController = $namespaceParts[0] === 'Drupal'
            && ($namespaceParts[2] ?? '') === 'Controller'
            && isset($namespaceParts[3]);
        if ($isAbstract || !$isController) {
            return false;
        }

        foreach ($methodAttributeNames as $attributeNames) {
            if (self::includesRoute($attributeNames)) {
                return true;
            }
        }

        // Without method routes, a class-level route goes to __invoke.
        return $isInvokable && self::includesRoute($classAttributeNames);
    }

    /**
     * Whether any of the attributes is a route.
     *
     * @param list<string> $attributeNames
     * @phpstan-pure
     */
    private static function includesRoute(array $attributeNames): bool
    {
        return array_intersect($attributeNames, self::ROUTE_ATTRIBUTES) !== [];
    }
}
