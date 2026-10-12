<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\EventSubscriber;

use Drupal\example\EventSubscriber\Handlers\ExceptionSubscriberWithHandlerVisibilities;
use Drupal\example\EventSubscriber\Handlers\HandlerNamedMethodOnPlainClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Reload\DeadCodeDetectorDrupal\Core\EventSubscriber\HttpExceptionHandlers;

final class HttpExceptionHandlersTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function handlerNames(): iterable
    {
        yield 'status code' => ['on404'];
        yield 'status class' => ['on5xx'];
        yield 'status code in other casing' => ['ON404'];
        yield 'status class in other casing' => ['On5XX'];
    }

    #[DataProvider('handlerNames')]
    public function testCoreCallsMethodOfHandlerName(string $methodName): void
    {
        self::assertTrue(HttpExceptionHandlers::isHandlerName($methodName));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function otherNames(): iterable
    {
        yield 'handler name with a suffix' => ['on404Page'];
        yield 'name of no status' => ['onSomethingElse'];
        yield 'too few digits' => ['on40'];
        yield 'the dispatching method itself' => ['onException'];
    }

    #[DataProvider('otherNames')]
    public function testCoreDoesNotCallMethodOfOtherName(string $methodName): void
    {
        self::assertFalse(HttpExceptionHandlers::isHandlerName($methodName));
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function handlers(): iterable
    {
        yield 'public handler' => [ExceptionSubscriberWithHandlerVisibilities::class, 'on404'];
        yield 'protected handler' => [ExceptionSubscriberWithHandlerVisibilities::class, 'on403'];
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('handlers')]
    public function testBaseClassCallsHandler(string $className, string $methodName): void
    {
        self::assertTrue(HttpExceptionHandlers::isHandler(new ReflectionMethod($className, $methodName)));
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function nonHandlers(): iterable
    {
        yield 'private handler, which the base class cannot reach'
            => [ExceptionSubscriberWithHandlerVisibilities::class, 'on410'];
        yield 'handler name outside an exception subscriber' => [HandlerNamedMethodOnPlainClass::class, 'on404'];
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('nonHandlers')]
    public function testBaseClassDoesNotCallNonHandler(string $className, string $methodName): void
    {
        self::assertFalse(HttpExceptionHandlers::isHandler(new ReflectionMethod($className, $methodName)));
    }
}
