<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Routing;

use Reload\DeadCodeDetectorDrupal\Support\Usage\OriginFiles;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsageProvider;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;

/**
 * Methods *.routing.yml makes Drupal call.
 */
final class RouteUsageProvider extends DeclaredUsageProvider
{
    /**
     * Declares what the reader finds, recording its files for the tests
     * excluder.
     */
    public function __construct(
        private readonly RouteDefinitions $routes,
        OriginFiles $originFiles,
    ) {
        parent::__construct($originFiles);
    }

    /**
     * The callables in every routing file.
     */
    protected function declaredUsages(): DeclaredUsages
    {
        return $this->routes->declaredUsages();
    }
}
