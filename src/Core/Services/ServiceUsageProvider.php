<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Services;

use Reload\DeadCodeDetectorDrupal\Support\Usage\OriginFiles;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsageProvider;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;

/**
 * Methods the service container calls, as *.services.yml declares them.
 */
final class ServiceUsageProvider extends DeclaredUsageProvider
{
    public function __construct(
        private readonly ServiceDefinitions $services,
        OriginFiles $originFiles,
    ) {
        parent::__construct($originFiles);
    }

    protected function declaredUsages(): DeclaredUsages
    {
        return $this->services->wiring()->declaredUsages();
    }
}
