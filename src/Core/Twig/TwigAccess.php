<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Twig;

use ReflectionMethod;
use ReflectionProperty;

use function strlen;
use function strncasecmp;
use function strtolower;
use function substr;

/**
 * What a template can reach on an object, as Twig resolves "x.foo".
 *
 * @see \Twig\Extension\CoreExtension::getAttribute()
 */
final class TwigAccess
{
    /**
     * Twig lists an object's methods with get_class_methods(): the public
     * ones, static ones included.
     *
     * @phpstan-pure
     */
    public static function callsMethod(ReflectionMethod $method): bool
    {
        return $method->isPublic();
    }

    /**
     * Twig reads properties off the object, which holds no static ones.
     *
     * @phpstan-pure
     */
    public static function readsProperty(ReflectionProperty $property): bool
    {
        return $property->isPublic() && !$property->isStatic();
    }

    /**
     * Lowercased names that reach the method, since Twig looks names up
     * case-insensitively: its own, and for getFoo(), isFoo() or hasFoo(),
     * "foo". Twig strips the prefix in any casing from any longer name.
     *
     * @return list<string>
     * @phpstan-pure
     */
    public static function namesReaching(string $methodName): array
    {
        $names = [strtolower($methodName)];
        foreach (['get', 'is', 'has'] as $prefix) {
            $prefixLength = strlen($prefix);
            if (strlen($methodName) > $prefixLength && strncasecmp($methodName, $prefix, $prefixLength) === 0) {
                $names[] = strtolower(substr($methodName, $prefixLength));
            }
        }

        return $names;
    }
}
