<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Services;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class ServiceUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/example/src/services.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'service constructor' => ['Drupal\example\Services\ServiceWithInheritedSetter::__construct'];
        yield 'setter in calls, declared on a parent class'
            => ['Drupal\example\Services\ParentWithSetter::setFromParent'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'service method no YAML names'
            => ['Unused Drupal\example\Services\ServiceWithInheritedSetter::methodNothingCalls'];
    }
}
