<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\LibraryCode\Data;

abstract class NonLibraryBaseClass
{
    public function methodOnlyTheParentDeclares(): void
    {
    }
}
