<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\ConfigSchema;

use Reload\DeadCodeDetectorDrupal\Support\Usage\OriginFiles;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsageProvider;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;

/**
 * Callback constraints in *.schema.yml.
 */
final class SchemaCallbackUsageProvider extends DeclaredUsageProvider
{
    /**
     * Declares what the reader finds, recording its files for the tests
     * excluder.
     */
    public function __construct(
        private readonly SchemaCallbacks $callbacks,
        OriginFiles $originFiles,
    ) {
        parent::__construct($originFiles);
    }

    /**
     * The callbacks in every schema file.
     */
    protected function declaredUsages(): DeclaredUsages
    {
        return $this->callbacks->declaredUsages();
    }
}
