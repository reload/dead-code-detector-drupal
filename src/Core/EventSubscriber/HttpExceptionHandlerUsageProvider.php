<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\EventSubscriber;

use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

/**
 * Exception subscribers' on{status code} and on{N}xx methods, which
 * HttpExceptionSubscriberBase::onException() calls by name on $this.
 *
 * @see \Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase::onException()
 */
final class HttpExceptionHandlerUsageProvider extends ReflectionBasedMemberUsageProvider
{
    /**
     * The handlers that onException() reaches by name.
     */
    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        return HttpExceptionHandlers::isHandler($method)
            ? VirtualUsageData::withNote('HTTP exception handler, called by HttpExceptionSubscriberBase')
            : null;
    }
}
