<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Permission;

use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;
use Reload\DeadCodeDetectorDrupal\Core\Callable\DrupalCallables;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFiles;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceDefinitions;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceWiring;
use Reload\DeadCodeDetectorDrupal\Support\Yaml\YamlDocument;

use function basename;
use function is_array;

/**
 * Methods that *.permissions.yml makes Drupal call: permission_callbacks,
 * which PermissionHandler resolves with the CallableResolver.
 *
 * @see \Drupal\user\PermissionHandler::buildPermissionsYaml()
 */
final class PermissionCallbacks
{
    private ?DeclaredUsages $declaredUsages = null;

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
            $this->drupalFiles->readWithSuffix(DrupalFiles::PERMISSIONS),
            $this->services->wiring(),
        );
    }

    /**
     * @param array<string, string> $yamlByFilePath
     * @phpstan-pure
     */
    public static function parse(array $yamlByFilePath, ServiceWiring $services): DeclaredUsages
    {
        $usages = DeclaredUsages::none();
        foreach ($yamlByFilePath as $filePath => $yaml) {
            $note = 'Permission callback in ' . basename($filePath);
            $callbacks = YamlDocument::parseMapping($yaml)['permission_callbacks'] ?? [];
            foreach (is_array($callbacks) ? $callbacks : [] as $callback) {
                $usages = DrupalCallables::declare($usages, $callback, $services, $note, $filePath);
            }
        }

        return $usages;
    }
}
