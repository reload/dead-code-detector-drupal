<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use ReflectionMethod;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\ClassAncestry;

use function ctype_digit;
use function in_array;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

/**
 * Methods HttpExceptionSubscriberBase::onException() calls by name on $this:
 * on{status code}, or on{N}xx for the status class.
 *
 * @see \Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase::onException()
 */
final class HttpExceptionHandlers
{
    /**
     * Called from the base class, which can reach protected methods but not
     * private ones.
     *
     * @phpstan-pure
     */
    public static function isHandler(ReflectionMethod $method): bool
    {
        return !$method->isPrivate()
            && self::isHandlerName($method->getName())
            && in_array(
                HttpExceptionSubscriberBase::class,
                ClassAncestry::typeNames($method->getDeclaringClass()),
                true,
            );
    }

    /**
     * Whether onException() reaches the method by its name alone.
     *
     * @phpstan-pure
     */
    public static function isHandlerName(string $methodName): bool
    {
        // PHP method names are case-insensitive, so core's "on" . $code reaches any casing.
        $name = strtolower($methodName);
        if (strlen($name) !== 5 || !str_starts_with($name, 'on')) {
            return false;
        }

        $status = substr($name, 2);

        return ctype_digit($status) || (ctype_digit($status[0]) && substr($status, 1) === 'xx');
    }
}
