<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredMethodUsage;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;

use function array_map;

final class DeclaredUsagesTest extends TestCase
{
    private const MODULE_ROUTING = '/site/modules/example/example.routing.yml';
    private const TEST_MODULE_ROUTING = '/site/modules/example/tests/modules/example_test/example_test.routing.yml';

    public function testMethodDeclaredByTwoFilesHasOneUsageFromBoth(): void
    {
        $usages = DeclaredUsages::none()
            ->withMethod('Drupal\example\RoutedByModuleAndTestModule', 'page', 'Route', self::MODULE_ROUTING)
            ->withMethod('Drupal\example\RoutedByModuleAndTestModule', 'page', 'Route', self::TEST_MODULE_ROUTING);

        self::assertSame(
            [[self::MODULE_ROUTING, self::TEST_MODULE_ROUTING]],
            array_map(
                static fn (DeclaredMethodUsage $usage): array => $usage->wiringFiles,
                $usages->usagesOf('Drupal\example\RoutedByModuleAndTestModule'),
            ),
        );
    }

    public function testMethodDeclaredTwiceByOneFileListsTheFileOnce(): void
    {
        $usages = DeclaredUsages::none()
            ->withMethod('Drupal\example\RoutedTwiceByOneFile', 'page', 'Route', self::MODULE_ROUTING)
            ->withMethod('Drupal\example\RoutedTwiceByOneFile', 'page', 'Route', self::MODULE_ROUTING);

        self::assertSame(
            [[self::MODULE_ROUTING]],
            array_map(
                static fn (DeclaredMethodUsage $usage): array => $usage->wiringFiles,
                $usages->usagesOf('Drupal\example\RoutedTwiceByOneFile'),
            ),
        );
    }
}
