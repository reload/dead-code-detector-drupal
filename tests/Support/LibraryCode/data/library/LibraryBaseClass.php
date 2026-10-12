<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\LibraryCode\Data;

abstract class LibraryBaseClass
{
    public const CONSTANT_THE_LIBRARY_READS = 1;

    protected int $propertyTheLibraryUses = 1;

    protected int $propertyTheLibraryWrites;

    public function methodTheLibraryCalls(): void
    {
    }
}
