<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Services;

use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFiles;
use Reload\DeadCodeDetectorDrupal\Support\Yaml\YamlDocument;

use function array_key_first;
use function basename;
use function count;
use function explode;
use function is_array;
use function is_string;
use function ltrim;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function substr;

/**
 * Services declared in *.services.yml, and the methods the container calls.
 *
 * Read from the YAML rather than taken from phpstan-drupal's ServiceMap, which
 * drops calls, tags, factories, configurators and the file a service came
 * from. The map is also static state, filled at bootstrap and due to change;
 * not building on it keeps an upstream move possible.
 *
 * @see \Drupal\Core\DependencyInjection\YamlFileLoader
 * @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass
 * @see \Drupal\Core\DependencyInjection\Compiler\RegisterAccessChecksPass
 * @see \Drupal\Core\Cache\Context\CacheContextsManager
 * @see https://www.drupal.org/docs/drupal-apis/services-and-dependency-injection/structure-of-a-service-file
 */
final class ServiceDefinitions
{
    private ?ServiceWiring $wiring = null;

    /**
     * Reads nothing until first use.
     */
    public function __construct(
        private readonly DrupalFiles $drupalFiles,
    ) {
    }

    /**
     * Read on first use.
     */
    public function wiring(): ServiceWiring
    {
        return $this->wiring ??= self::parse($this->drupalFiles->readWithSuffix(DrupalFiles::SERVICES));
    }

    /**
     * Each service's class, and the methods the container calls on it: the
     * constructor, calls, tag methods, factories and configurators. Aliases and
     * parents are followed across files.
     *
     * @param array<string, string> $yamlByFilePath
     * @phpstan-pure
     */
    public static function parse(array $yamlByFilePath): ServiceWiring
    {
        $definitions = [];
        $aliasTargetById = [];
        foreach ($yamlByFilePath as $filePath => $yaml) {
            foreach (self::parseServices($yaml) as $serviceId => $definition) {
                $aliasTarget = self::aliasTarget($definition);
                if ($aliasTarget !== null) {
                    $aliasTargetById[$serviceId] = $aliasTarget;
                } elseif (is_array($definition)) {
                    $definitions[$serviceId] = [$definition, $filePath];
                }
            }
        }

        $classByServiceId = [];
        foreach ($definitions as $serviceId => $definition) {
            $className = self::resolveClass($serviceId, $definitions, []);
            if ($className !== null) {
                $classByServiceId[$serviceId] = $className;
            }
        }

        // An alias stands for the service it names, so "alias:method" callables reach its class.
        foreach ($aliasTargetById as $aliasId => $aliasTarget) {
            $visited = [$aliasId => true];
            while (isset($aliasTargetById[$aliasTarget]) && !isset($visited[$aliasTarget])) {
                $visited[$aliasTarget] = true;
                $aliasTarget = $aliasTargetById[$aliasTarget];
            }

            if (isset($classByServiceId[$aliasTarget])) {
                $classByServiceId[$aliasId] = $classByServiceId[$aliasTarget];
            }
        }

        $usages = DeclaredUsages::none();
        foreach ($definitions as $serviceId => [$definition, $filePath]) {
            $service = sprintf('service %s in %s', $serviceId, basename($filePath));
            $className = $classByServiceId[$serviceId] ?? null;

            if ($className !== null) {
                // Factories construct the service themselves; abstract services are templates for others.
                if (!isset($definition['factory']) && ($definition['abstract'] ?? false) !== true) {
                    $note = 'Constructed by the service container as ' . $service;
                    $usages = $usages->withMethod($className, '__construct', $note, $filePath);
                }

                foreach (self::callMethods($definition['calls'] ?? null) as $methodName) {
                    $note = 'Called by the service container on ' . $service;
                    $usages = $usages->withMethod($className, $methodName, $note, $filePath);
                }

                foreach (self::taggedMethods($definition['tags'] ?? null) as $tagName => $methodNames) {
                    foreach ($methodNames as $methodName) {
                        $note = sprintf('Called by the collector of tag %s on %s', $tagName, $service);
                        $usages = $usages->withMethod($className, $methodName, $note, $filePath);
                    }
                }
            }

            $callableByRole = [
                'Factory' => self::factoryCallable($definition['factory'] ?? null),
                'Configurator' => $definition['configurator'] ?? null,
            ];
            foreach ($callableByRole as $role => $definedCallable) {
                $callable = self::resolveCallable($definedCallable, $classByServiceId);
                if ($callable !== null) {
                    $usages = $usages->withMethod($callable[0], $callable[1], $role . ' of ' . $service, $filePath);
                }
            }
        }

        return new ServiceWiring($classByServiceId, $usages);
    }

    /**
     * The service definitions in one file, keyed by id.
     *
     * @return array<string, mixed>
     * @phpstan-pure
     */
    private static function parseServices(string $yaml): array
    {
        $services = YamlDocument::parseMapping($yaml)['services'] ?? null;
        if (!is_array($services)) {
            return [];
        }

        $definitions = [];
        foreach ($services as $serviceId => $definition) {
            // "_defaults" and "_instanceof" configure other services.
            if (!is_string($serviceId) || str_starts_with($serviceId, '_')) {
                continue;
            }

            // A class name as service id may have no definition at all.
            $definitions[$serviceId] = $definition ?? [];
        }

        return $definitions;
    }

    /**
     * The service an alias names: "id: '@other'" or "id: {alias: other}".
     *
     * @phpstan-pure
     */
    private static function aliasTarget(mixed $definition): ?string
    {
        if (is_string($definition) && str_starts_with($definition, '@')) {
            return substr($definition, 1);
        }

        $aliasTarget = is_array($definition) ? $definition['alias'] ?? null : null;

        return is_string($aliasTarget) ? $aliasTarget : null;
    }

    /**
     * The class of a service: its own, its parent's, or its id when that is a
     * class name.
     *
     * @param array<string, array{array<mixed>, string}> $definitions
     * @param array<string, true> $visited
     * @phpstan-pure
     */
    private static function resolveClass(string $serviceId, array $definitions, array $visited): ?string
    {
        if (!isset($definitions[$serviceId]) || isset($visited[$serviceId])) {
            return null;
        }

        $definition = $definitions[$serviceId][0];
        $className = $definition['class'] ?? null;
        if (is_string($className) && self::isClassName($className)) {
            return ltrim($className, '\\');
        }

        $parent = $definition['parent'] ?? null;
        if (is_string($parent)) {
            $parentClassName = self::resolveClass($parent, $definitions, $visited + [$serviceId => true]);
            if ($parentClassName !== null) {
                return $parentClassName;
            }
        }

        return self::isClassName($serviceId) ? ltrim($serviceId, '\\') : null;
    }

    /**
     * The methods "calls" names, in each notation Symfony accepts.
     *
     * @return list<string>
     * @phpstan-pure
     */
    private static function callMethods(mixed $calls): array
    {
        if (!is_array($calls)) {
            return [];
        }

        $methodNames = [];
        foreach ($calls as $call) {
            $methodName = match (true) {
                // [method, arguments]
                is_array($call) && is_string($call[0] ?? null) => $call[0],
                // {method: method, arguments: arguments}
                is_array($call) && is_string($call['method'] ?? null) => $call['method'],
                // {method: arguments}
                is_array($call) && count($call) === 1 && is_string(array_key_first($call)) => array_key_first($call),
                default => null,
            };
            if ($methodName !== null) {
                $methodNames[] = $methodName;
            }
        }

        return $methodNames;
    }

    /**
     * Methods core calls on tagged services without an interface promising them.
     *
     * @return array<string, list<string>>
     * @phpstan-pure
     */
    private static function taggedMethods(mixed $tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        $methodNamesByTag = [];
        foreach ($tags as $tag) {
            $tagName = is_array($tag) ? $tag['name'] ?? null : $tag;
            if (!is_string($tagName)) {
                continue;
            }

            $methodNames = match ($tagName) {
                // Access checkers name their method in the tag, "access" by default.
                'access_check' => [is_array($tag) && is_string($tag['method'] ?? null) ? $tag['method'] : 'access'],
                // CacheContextsManager calls these whether or not CacheContextInterface is implemented.
                'cache.context' => ['getLabel', 'getContext', 'getCacheableMetadata'],
                // TaggedHandlersPass calls the collector's method, "addHandler" by default, per tagged service.
                'service_collector' => [
                    is_array($tag) && is_string($tag['call'] ?? null) ? $tag['call'] : 'addHandler',
                ],
                default => [],
            };
            if ($methodNames !== []) {
                $methodNamesByTag[$tagName] = [...$methodNamesByTag[$tagName] ?? [], ...$methodNames];
            }
        }

        return $methodNamesByTag;
    }

    /**
     * Drupal's YamlFileLoader reads a "service_id:method" factory as ['@service_id', 'method'].
     *
     * @phpstan-pure
     */
    private static function factoryCallable(mixed $factory): mixed
    {
        if (is_string($factory) && str_contains($factory, ':') && !str_contains($factory, '::')) {
            [$serviceId, $methodName] = explode(':', $factory, 2);

            return ['@' . $serviceId, $methodName];
        }

        return $factory;
    }

    /**
     * The class and method a factory or configurator names, if its class is
     * known.
     *
     * @param array<string, string> $classByServiceId
     * @return array{string, string}|null
     * @phpstan-pure
     */
    private static function resolveCallable(mixed $callable, array $classByServiceId): ?array
    {
        [$target, $methodName] = match (true) {
            is_array($callable) && is_string($callable[0] ?? null) && is_string($callable[1] ?? null)
                => [$callable[0], $callable[1]],
            is_string($callable) && str_contains($callable, '::') => explode('::', $callable, 2),
            // An invokable service.
            is_string($callable) && str_starts_with($callable, '@') => [$callable, '__invoke'],
            default => [null, null],
        };
        if ($target === null || $methodName === null) {
            return null;
        }

        if (str_starts_with($target, '@')) {
            $className = $classByServiceId[ltrim($target, '@')] ?? null;

            return $className === null ? null : [$className, $methodName];
        }

        return self::isClassName($target) ? [ltrim($target, '\\'), $methodName] : null;
    }

    /**
     * Tells class names from service ids and parameters.
     *
     * @phpstan-pure
     */
    private static function isClassName(string $name): bool
    {
        // Service ids and parameters ("%name%") are not namespaced; class names are.
        return str_contains($name, '\\');
    }
}
