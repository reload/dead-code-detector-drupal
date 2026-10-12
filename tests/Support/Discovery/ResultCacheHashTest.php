<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\Discovery;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFilesResultCacheMetaExtension;

/**
 * How the result cache hash combines what it knows of each file.
 */
final class ResultCacheHashTest extends TestCase
{
    public function testHashChangesWithAFilesContents(): void
    {
        self::assertNotSame(
            DrupalFilesResultCacheMetaExtension::combinedHash(['example/example.routing.yml content']),
            DrupalFilesResultCacheMetaExtension::combinedHash(['example/example.routing.yml edited content']),
        );
    }

    public function testHashIsTheSameWhicheverOrderFilesAreFoundIn(): void
    {
        self::assertSame(
            DrupalFilesResultCacheMetaExtension::combinedHash(['a.yml first', 'b.yml second']),
            DrupalFilesResultCacheMetaExtension::combinedHash(['b.yml second', 'a.yml first']),
        );
    }
}
