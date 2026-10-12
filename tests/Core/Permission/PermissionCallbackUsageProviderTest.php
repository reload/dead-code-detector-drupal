<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Permission;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class PermissionCallbackUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/example/src/permission-callbacks.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'permission callback' => ['Drupal\example\Permission\ClassWithPermissionCallback::permissions'];
        yield 'permission callback class constructor, as the class resolver instantiates it'
            => ['Drupal\example\Permission\ClassWithPermissionCallback::__construct'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'callback class method no YAML names'
            => ['Unused Drupal\example\Permission\ClassWithPermissionCallback::methodNothingCalls'];
    }
}
