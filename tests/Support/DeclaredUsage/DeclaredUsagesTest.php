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
    private const OTHER_MODULE_ROUTING = '/site/modules/other/other.routing.yml';

    public function testMethodDeclaredByTwoFilesHasOneUsageFromBoth(): void
    {
        $usages = DeclaredUsages::none()
            ->withMethod('Drupal\example\RoutedByModuleAndTestModule', 'page', 'Route', self::MODULE_ROUTING)
            ->withMethod('Drupal\example\RoutedByModuleAndTestModule', 'page', 'Route', self::TEST_MODULE_ROUTING);

        self::assertSame(
            [[self::MODULE_ROUTING, self::TEST_MODULE_ROUTING]],
            self::wiringFilesOf($usages, 'Drupal\example\RoutedByModuleAndTestModule'),
        );
    }

    public function testMethodDeclaredByThreeFilesKeepsEveryFile(): void
    {
        $usages = DeclaredUsages::none()
            ->withMethod('Drupal\example\RoutedByThreeFiles', 'page', 'Route', self::MODULE_ROUTING)
            ->withMethod('Drupal\example\RoutedByThreeFiles', 'page', 'Route', self::TEST_MODULE_ROUTING)
            ->withMethod('Drupal\example\RoutedByThreeFiles', 'page', 'Route', self::OTHER_MODULE_ROUTING);

        self::assertSame(
            [[self::MODULE_ROUTING, self::TEST_MODULE_ROUTING, self::OTHER_MODULE_ROUTING]],
            self::wiringFilesOf($usages, 'Drupal\example\RoutedByThreeFiles'),
        );
    }

    public function testMethodDeclaredTwiceByOneFileListsTheFileOnce(): void
    {
        $usages = DeclaredUsages::none()
            ->withMethod('Drupal\example\RoutedTwiceByOneFile', 'page', 'Route', self::MODULE_ROUTING)
            ->withMethod('Drupal\example\RoutedTwiceByOneFile', 'page', 'Route', self::MODULE_ROUTING);

        self::assertSame([[self::MODULE_ROUTING]], self::wiringFilesOf($usages, 'Drupal\example\RoutedTwiceByOneFile'));
    }

    public function testClassIsFoundInAnyCasing(): void
    {
        $usages = DeclaredUsages::none()
            ->withMethod('drupal\example\casedclass', 'page', 'Route', self::MODULE_ROUTING);

        self::assertSame(['page'], DeclaredMethods::of($usages, 'Drupal\example\CasedClass'));
    }

    public function testMethodDeclaredInTwoCasingsIsOneUsage(): void
    {
        $usages = DeclaredUsages::none()
            ->withMethod('Drupal\example\CasedMethod', 'page', 'Route', self::MODULE_ROUTING)
            ->withMethod('Drupal\example\CasedMethod', 'PAGE', 'Route', self::TEST_MODULE_ROUTING);

        self::assertSame(
            [[self::MODULE_ROUTING, self::TEST_MODULE_ROUTING]],
            self::wiringFilesOf($usages, 'Drupal\example\CasedMethod'),
        );
    }

    /**
     * @return list<list<string>>
     */
    private static function wiringFilesOf(DeclaredUsages $usages, string $className): array
    {
        return array_map(
            static fn (DeclaredMethodUsage $usage): array => $usage->wiringFiles,
            $usages->usagesOf($className),
        );
    }
}
