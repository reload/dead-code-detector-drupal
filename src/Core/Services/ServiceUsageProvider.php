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
    /**
     * Declares what the reader finds, recording its files for the tests
     * excluder.
     */
    public function __construct(
        private readonly ServiceDefinitions $services,
        OriginFiles $originFiles,
    ) {
        parent::__construct($originFiles);
    }

    /**
     * The methods the container calls, from every services file.
     */
    protected function declaredUsages(): DeclaredUsages
    {
        return $this->services->wiring()->declaredUsages();
    }
}
