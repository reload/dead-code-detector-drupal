<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Permission;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;
use Reload\DeadCodeDetectorDrupal\Core\Permission\PermissionCallbacks;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceDefinitions;
use Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage\DeclaredMethods;

final class PermissionCallbacksTest extends TestCase
{
    public function testClassCallbackIsCalledOnClassResolverInstance(): void
    {
        $usages = self::parse(<<<'YAML'
            permission_callbacks:
              - '\Drupal\example\ClassWithPermissionCallback::permissions'
            YAML);

        self::assertContains('permissions', DeclaredMethods::of($usages, 'Drupal\example\ClassWithPermissionCallback'));
    }

    public function testStaticPermissionsDeclareNothing(): void
    {
        $usages = self::parse(<<<'YAML'
            administer example:
              title: 'Administer example'
            YAML);

        self::assertTrue($usages->isEmpty());
    }

    private static function parse(string $yaml): DeclaredUsages
    {
        return PermissionCallbacks::parse(
            ['/site/example/example.permissions.yml' => $yaml],
            ServiceDefinitions::parse([]),
        );
    }
}
