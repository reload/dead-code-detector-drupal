<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Permission;

use Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage\WiringRuleTestCase;

final class PermissionCallbackUsageProviderTest extends WiringRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/example/src/permission-callbacks.php'];
    }

    protected static function fixtureNamespace(): string
    {
        return 'Drupal\example\Permission';
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
