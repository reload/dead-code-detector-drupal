<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\MethodsCalledByNameUsageProvider;

/**
 * Access checkers' access(), which AccessManager calls with arguments resolved
 * per checker; AccessInterface only marks them.
 *
 * @see \Drupal\Core\Access\AccessManager::performCheck()
 * @see https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes/advanced-route-access-checking
 */
final class AccessCheckerUsageProvider extends MethodsCalledByNameUsageProvider
{
    /**
     * The marker interface of access checkers.
     */
    protected function typeName(): string
    {
        return AccessInterface::class;
    }

    /**
     * AccessManager's default; a tag naming another method is read from the
     * service wiring.
     */
    protected function methodNames(): array
    {
        return ['access'];
    }

    /**
     * Names AccessManager as the caller.
     */
    protected function note(): string
    {
        return 'Access checker, called by AccessManager';
    }
}
