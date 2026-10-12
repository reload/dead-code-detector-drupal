<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\EventSubscriber;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class HttpExceptionHandlerUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/exception-subscribers.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'status code handler of an exception subscriber'
            => ['Drupal\example\EventSubscriber\ExceptionSubscriberHandlingByName::on404'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'handler-named method outside an exception subscriber'
            => ['Unused Drupal\example\EventSubscriber\HandlerNamedMethodOutsideSubscriber::on404'];
    }
}
