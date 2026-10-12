<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\TestCode;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Core\TestCode\TestPaths;

final class TestPathsTest extends TestCase
{
    public function testFileInExtensionTestsDirectoryIsTestCode(): void
    {
        $testPaths = new TestPaths(['/site/web/modules/custom']);

        self::assertTrue($testPaths->contains('/site/web/modules/custom/example/tests/src/Kernel/ExampleTest.php'));
    }

    public function testFileOutsideTestsDirectoryIsNotTestCode(): void
    {
        $testPaths = new TestPaths(['/site/web/modules/custom']);

        self::assertFalse($testPaths->contains('/site/web/modules/custom/example/src/Example.php'));
    }

    public function testFileInAnalysedTestsDirectoryIsTestCode(): void
    {
        $testPaths = new TestPaths(['/site/tests']);

        self::assertTrue($testPaths->contains('/site/tests/src/ExistingSite/ExampleTest.php'));
    }

    public function testTestsDirectoryAboveAnalysedPathDoesNotCount(): void
    {
        $testPaths = new TestPaths(['/builds/tests/site/web/modules/custom']);

        self::assertFalse($testPaths->contains('/builds/tests/site/web/modules/custom/example/src/Example.php'));
    }

    public function testFileOutsideAnalysedPathsIsNotTestCode(): void
    {
        $testPaths = new TestPaths(['/site/web/modules/custom']);

        self::assertFalse($testPaths->contains('/site/web/core/tests/Drupal/KernelTests/KernelTestBase.php'));
    }

    public function testSiblingOfAnalysedPathIsNotTestCode(): void
    {
        $testPaths = new TestPaths(['/site/web/modules/custom']);

        self::assertFalse($testPaths->contains('/site/web/modules/custom_other/example/tests/src/ExampleTest.php'));
    }

    public function testTestsOfAnAnalysedExtensionAreTestCode(): void
    {
        $testPaths = new TestPaths(['/site/web/modules/custom/example']);

        self::assertTrue($testPaths->contains('/site/web/modules/custom/example/tests/src/ExampleTest.php'));
    }
}
