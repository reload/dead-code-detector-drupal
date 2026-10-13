<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\Discovery;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFiles;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\ExcludedPaths;

use function array_keys;
use function array_map;
use function basename;

/**
 * Finding files is the thing under test, so these read a fixture tree.
 */
final class DrupalFilesTest extends TestCase
{
    public function testWiringFilesBelowAnalysedDirectoryAreFound(): void
    {
        $drupalFiles = new DrupalFiles([__DIR__ . '/data/tree']);

        self::assertSame(
            ['example.schema.yml', 'example.services.yml', 'template-in-tree.html.twig'],
            array_map(basename(...), $drupalFiles->all()),
        );
    }

    public function testWiringFilesInSymlinkedDirectoryAreFound(): void
    {
        $drupalFiles = new DrupalFiles([__DIR__ . '/data/tree-with-symlink']);

        self::assertSame(['symlinked.services.yml'], array_map(basename(...), $drupalFiles->all()));
    }

    public function testTemplatesWithoutHtmlInTheirNameAreFound(): void
    {
        $drupalFiles = new DrupalFiles([__DIR__ . '/data/component-without-html-suffix']);

        self::assertSame(['component.twig'], array_map(basename(...), $drupalFiles->all()));
    }

    public function testTemplatesAreNotFoundWhileTwigMatchingIsOff(): void
    {
        $drupalFiles = new DrupalFiles([__DIR__ . '/data/tree'], twigTemplatesEnabled: false);

        self::assertNotContains(__DIR__ . '/data/tree/example/template-in-tree.html.twig', $drupalFiles->all());
    }

    public function testWiringFilesInExcludedPathsAreSkipped(): void
    {
        $drupalFiles = new DrupalFiles(
            [__DIR__ . '/data/tree'],
            new ExcludedPaths([__DIR__ . '/data/tree/example/example.services.yml']),
        );

        self::assertSame(
            ['example.schema.yml', 'template-in-tree.html.twig'],
            array_map(basename(...), $drupalFiles->all()),
        );
    }

    public function testFileIsKnownByItsPathWithinTheAnalysedDirectory(): void
    {
        $drupalFiles = new DrupalFiles([__DIR__ . '/data/tree']);

        self::assertSame(
            'example/example.services.yml',
            $drupalFiles->relativePathByFilePath()[__DIR__ . '/data/tree/example/example.services.yml'],
        );
    }

    public function testAnalysedFileIsFound(): void
    {
        $drupalFiles = new DrupalFiles([__DIR__ . '/data/single-file.routing.yml']);

        self::assertSame(['single-file.routing.yml'], array_map(basename(...), $drupalFiles->all()));
    }

    public function testAnalysedFileWithoutWiringSuffixIsSkipped(): void
    {
        $drupalFiles = new DrupalFiles([
            __DIR__ . '/data/analysed-file-without-wiring-suffix.txt',
            __DIR__ . '/data/single-file.routing.yml',
        ]);

        self::assertSame(['single-file.routing.yml'], array_map(basename(...), $drupalFiles->all()));
    }

    public function testFilesAreReadBySuffix(): void
    {
        $drupalFiles = new DrupalFiles([__DIR__ . '/data/tree']);

        self::assertSame(
            ['example.services.yml'],
            array_map(basename(...), array_keys($drupalFiles->readWithSuffix(DrupalFiles::SERVICES))),
        );
    }
}
