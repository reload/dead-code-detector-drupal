<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\LibraryCode\Data;

final class LibraryClassInPaths
{
    public const CONSTANT_NOTHING_READS = 1;

    private int $propertyNothingUses = 1;

    public function methodNothingCalls(): void
    {
    }
}
