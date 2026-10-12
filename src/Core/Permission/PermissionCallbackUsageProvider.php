<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Permission;

use Reload\DeadCodeDetectorDrupal\Support\Usage\OriginFiles;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsageProvider;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;

/**
 * Permission callbacks in *.permissions.yml.
 */
final class PermissionCallbackUsageProvider extends DeclaredUsageProvider
{
    public function __construct(
        private readonly PermissionCallbacks $callbacks,
        OriginFiles $originFiles,
    ) {
        parent::__construct($originFiles);
    }

    protected function declaredUsages(): DeclaredUsages
    {
        return $this->callbacks->declaredUsages();
    }
}
