<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\LibraryCode;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\LibraryCode\LibraryPaths;

final class LibraryPathsTest extends TestCase
{
    public function testFileInsideInstallPathBelongsToPackage(): void
    {
        $libraryPaths = LibraryPaths::fromDirectories(['/site/library' => 'example/library']);

        self::assertSame('example/library', $libraryPaths->findPackageName('/site/library/src/Inside.php'));
    }

    public function testDirectorySharingPrefixWithInstallPathIsNotLibrary(): void
    {
        $libraryPaths = LibraryPaths::fromDirectories(['/site/library' => 'example/library']);

        self::assertNull($libraryPaths->findPackageName('/site/library_sibling/src/SharesPrefix.php'));
    }

    public function testFilePathWithRelativeSegmentsBelongsToPackage(): void
    {
        $libraryPaths = LibraryPaths::fromDirectories(['/site/library' => 'example/library']);

        self::assertSame(
            'example/library',
            $libraryPaths->findPackageName('/site/vendor/composer/../../library/src/Inside.php'),
        );
    }

    public function testInstallPathWithRelativeSegmentsOwnsItsFiles(): void
    {
        $libraryPaths = LibraryPaths::fromDirectories(['/site/vendor/composer/../../library/' => 'example/library']);

        self::assertSame('example/library', $libraryPaths->findPackageName('/site/library/src/Inside.php'));
    }

    public function testFileInsidePharBelongsToPackage(): void
    {
        $libraryPaths = LibraryPaths::fromDirectories(['/site/phar-shipped' => 'example/phar-shipped']);

        self::assertSame(
            'example/phar-shipped',
            $libraryPaths->findPackageName('phar:///site/phar-shipped/tool.phar/src/InsidePhar.php'),
        );
    }

    public function testNestedInstallPathOwnsItsFiles(): void
    {
        $libraryPaths = LibraryPaths::fromDirectories([
            '/site/outer' => 'example/outer',
            '/site/outer/inner' => 'example/inner',
        ]);

        self::assertSame('example/inner', $libraryPaths->findPackageName('/site/outer/inner/src/Inside.php'));
    }

    public function testEmptyInstallPathOwnsNoFiles(): void
    {
        $libraryPaths = LibraryPaths::fromDirectories(['' => 'example/empty-install-path']);

        self::assertNull($libraryPaths->findPackageName('/site/custom/src/Unowned.php'));
    }

    public function testRootPackageIsNotLibrary(): void
    {
        $libraryPaths = LibraryPaths::parse([
            [
                'root' => ['install_path' => '/site/vendor/composer/../../'],
                'versions' => ['example/root' => ['install_path' => '/site/vendor/composer/../../']],
            ],
        ]);

        self::assertNull($libraryPaths->findPackageName('/site/custom/src/RootOwned.php'));
    }

    public function testPackageWithoutInstallPathOwnsNoFiles(): void
    {
        $libraryPaths = LibraryPaths::parse([
            [
                'versions' => [
                    'example/metapackage' => ['install_path' => null],
                    'example/provided-only' => ['provided' => ['1.0']],
                ],
            ],
        ]);

        self::assertNull($libraryPaths->findPackageName('/site/custom/src/Unowned.php'));
    }

    public function testPackageListedAfterMetapackageIsLibrary(): void
    {
        $libraryPaths = LibraryPaths::parse([
            [
                'versions' => [
                    'example/metapackage' => ['install_path' => null],
                    'example/after-metapackage' => ['install_path' => '/site/after'],
                ],
            ],
        ]);

        self::assertSame('example/after-metapackage', $libraryPaths->findPackageName('/site/after/src/Inside.php'));
    }

    public function testPackagesFromEveryAutoloaderAreLibrary(): void
    {
        $libraryPaths = LibraryPaths::parse([
            ['versions' => ['example/from-first-autoloader' => ['install_path' => '/site/first']]],
            ['versions' => ['example/from-second-autoloader' => ['install_path' => '/site/second']]],
        ]);

        self::assertSame(
            'example/from-second-autoloader',
            $libraryPaths->findPackageName('/site/second/src/Inside.php'),
        );
    }

    public function testRealDirectoryOfSymlinkedInstallPathBelongsToSamePackage(): void
    {
        $symlinkedDirectory = '/site/vendor/example/path-repository';
        $libraryPaths = LibraryPaths::fromDirectories([$symlinkedDirectory => 'example/path-repository'])
            ->withRealDirectories([$symlinkedDirectory => '/site/packages/path-repository']);

        self::assertSame(
            'example/path-repository',
            $libraryPaths->findPackageName('/site/packages/path-repository/src/Inside.php'),
        );
    }

    public function testRealDirectoryOwnedByAnotherPackageKeepsItsOwner(): void
    {
        $libraryPaths = LibraryPaths::fromDirectories([
            '/site/vendor/example/symlink' => 'example/symlink',
            '/site/target' => 'example/target-owner',
        ])->withRealDirectories(['/site/vendor/example/symlink' => '/site/target']);

        self::assertSame('example/target-owner', $libraryPaths->findPackageName('/site/target/src/Inside.php'));
    }
}
