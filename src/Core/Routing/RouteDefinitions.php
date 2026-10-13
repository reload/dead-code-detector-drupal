<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Routing;

use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;
use Reload\DeadCodeDetectorDrupal\Core\Callable\DrupalCallables;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFiles;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceDefinitions;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceWiring;
use Reload\DeadCodeDetectorDrupal\Support\Yaml\YamlDocument;

use function basename;
use function is_array;
use function is_string;
use function sprintf;
use function str_contains;

/**
 * Methods that *.routing.yml makes Drupal call.
 *
 * @see \Drupal\Core\Controller\ControllerResolver::getControllerFromDefinition()
 * @see \Drupal\Core\Routing\YamlRouteDiscovery
 * @see https://www.drupal.org/docs/drupal-apis/routing-system/structure-of-routes
 */
final class RouteDefinitions
{
    /**
     * Callable defaults and requirements, with the role they play.
     */
    private const CALLABLES = [
        ['defaults', '_controller', 'Controller'],
        ['defaults', '_title_callback', 'Title callback'],
        ['requirements', '_custom_access', 'Access callback'],
    ];

    private ?DeclaredUsages $declaredUsages = null;

    /**
     * Reads nothing until first use.
     */
    public function __construct(
        private readonly DrupalFiles $drupalFiles,
        private readonly ServiceDefinitions $services,
    ) {
    }

    /**
     * Read on first use.
     */
    public function declaredUsages(): DeclaredUsages
    {
        return $this->declaredUsages ??= self::parse(
            $this->drupalFiles->readWithSuffix(DrupalFiles::ROUTING),
            $this->services->wiring(),
        );
    }

    /**
     * The controllers, title callbacks, access callbacks and route callbacks
     * each routing file names.
     *
     * @param array<string, string> $yamlByFilePath
     * @phpstan-pure
     */
    public static function parse(array $yamlByFilePath, ServiceWiring $services): DeclaredUsages
    {
        $usages = DeclaredUsages::none();
        foreach ($yamlByFilePath as $filePath => $yaml) {
            $fileName = basename($filePath);
            foreach (YamlDocument::parseMapping($yaml) as $routeName => $route) {
                if ($routeName === 'route_callbacks' && is_array($route)) {
                    foreach ($route as $routeCallback) {
                        $note = 'Route callback in ' . $fileName;
                        $usages = DrupalCallables::declare($usages, $routeCallback, $services, $note, $filePath);
                    }
                    continue;
                }

                if (!is_string($routeName) || !is_array($route)) {
                    continue;
                }

                $where = sprintf('route %s in %s', $routeName, $fileName);
                foreach (self::CALLABLES as [$section, $key, $role]) {
                    $definition = is_array($route[$section] ?? null) ? $route[$section][$key] ?? null : null;
                    $note = $role . ' of ' . $where;
                    $usages = DrupalCallables::declare($usages, $definition, $services, $note, $filePath);
                }

                $form = is_array($route['defaults'] ?? null) ? $route['defaults']['_form'] ?? null : null;
                if (is_string($form) && str_contains($form, '\\')) {
                    $note = 'Form of ' . $where . ', instantiated by the class resolver';
                    $usages = $usages->withMethod($form, '__construct', $note, $filePath);
                }
            }
        }

        return $usages;
    }
}
