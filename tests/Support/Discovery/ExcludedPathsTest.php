<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\Discovery;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\ExcludedPaths;

final class ExcludedPathsTest extends TestCase
{
    #[DataProvider('excludedFiles')]
    public function testContainsExcludedFile(string $excludedPath, string $filePath): void
    {
        self::assertTrue((new ExcludedPaths([$excludedPath]))->contains($filePath));
    }

    #[DataProvider('includedFiles')]
    public function testDoesNotContainOtherFile(string $excludedPath, string $filePath): void
    {
        self::assertFalse((new ExcludedPaths([$excludedPath]))->contains($filePath));
    }

    /**
     * @param array{analyse?: list<string>, analyseAndScan?: list<string>} $excludePathsParameter
     */
    #[DataProvider('excludePathsParameters')]
    public function testReadsPhpstansParameter(array $excludePathsParameter): void
    {
        $excludedPaths = ExcludedPaths::fromParameter($excludePathsParameter);

        self::assertTrue($excludedPaths->contains('/site/excluded/a.routing.yml'));
    }

    /**
     * @return iterable<string, array{array{analyse?: list<string>, analyseAndScan?: list<string>}}>
     */
    public static function excludePathsParameters(): iterable
    {
        yield 'paths excluded from analysis' => [['analyse' => ['/site/excluded']]];
        yield 'paths excluded from analysis and scanning' => [['analyseAndScan' => ['/site/excluded']]];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function excludedFiles(): iterable
    {
        yield 'file below an excluded directory' => ['/site/contrib', '/site/contrib/module/a.routing.yml'];
        yield 'excluded file itself' => ['/site/a.routing.yml', '/site/a.routing.yml'];
        yield 'file matching a pattern' => ['*/tests/*', '/site/module/tests/a.routing.yml'];
        yield 'excluded directory written with a trailing slash' => ['/site/contrib/', '/site/contrib/a.routing.yml'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function includedFiles(): iterable
    {
        yield 'directory only sharing a prefix' => ['/site/contrib', '/site/contrib-extra/a.routing.yml'];
        yield 'file matching no pattern' => ['*/tests/*', '/site/module/src/a.routing.yml'];
    }
}
