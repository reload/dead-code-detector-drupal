<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Callable;

use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceWiring;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;

use function ctype_alnum;
use function ctype_alpha;
use function explode;
use function is_string;
use function str_contains;
use function strtr;
use function substr_count;

/**
 * Callables in Drupal's string notations, as its CallableResolver resolves
 * them: "Class::method", "service:method", "Class:method" and an invokable
 * "Class".
 *
 * @see \Drupal\Core\Utility\CallableResolver::getCallableFromDefinition()
 * @see \Drupal\Core\DependencyInjection\ClassResolver::getInstanceFromDefinition()
 */
final class DrupalCallables
{
    /**
     * Splits a definition as CallableResolver does: on "::", else on a single
     * ":". Which side is a class or a service is the reader's call.
     *
     * @return array{string, string}|null
     *   The class or service id, and the method name.
     * @phpstan-pure
     */
    public static function split(string $definition): ?array
    {
        if (str_contains($definition, '::')) {
            [$target, $methodName] = explode('::', $definition, 2);
        } elseif (substr_count($definition, ':') === 1) {
            [$target, $methodName] = explode(':', $definition, 2);
        } else {
            return null;
        }

        return $target !== '' && $methodName !== '' ? [$target, $methodName] : null;
    }

    /**
     * Whether a name is one PHP allows for a method, as in Form API's
     * "::method".
     *
     * @phpstan-pure
     */
    public static function isMethodName(string $name): bool
    {
        return $name !== ''
            && (ctype_alpha($name[0]) || $name[0] === '_')
            && ctype_alnum(strtr($name, ['_' => '']));
    }

    /**
     * @phpstan-pure
     */
    public static function declare(
        DeclaredUsages $usages,
        mixed $definition,
        ServiceWiring $services,
        string $note,
        string $filePath,
    ): DeclaredUsages {
        if (!is_string($definition)) {
            return $usages;
        }

        $split = self::split($definition);
        if ($split === null) {
            // An invokable class, which the class resolver instantiates.
            return str_contains($definition, '\\')
                ? self::constructedAndCalled($usages, $definition, '__invoke', $note, $filePath)
                : $usages;
        }

        [$target, $methodName] = $split;
        $serviceClassName = $services->classOf($target);
        if ($serviceClassName !== null) {
            // The service container constructs it; see ServiceDefinitions.
            return $usages->withMethod($serviceClassName, $methodName, $note, $filePath);
        }

        // Service ids are not namespaced; class names are.
        return str_contains($target, '\\')
            ? self::constructedAndCalled($usages, $target, $methodName, $note, $filePath)
            : $usages;
    }

    /**
     * @phpstan-pure
     */
    private static function constructedAndCalled(
        DeclaredUsages $usages,
        string $className,
        string $methodName,
        string $note,
        string $filePath,
    ): DeclaredUsages {
        return $usages
            ->withMethod($className, $methodName, $note, $filePath)
            ->withMethod($className, '__construct', $note . ', instantiated by the class resolver', $filePath);
    }
}
