<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\LibraryCode\Data;

trait LibraryTraitNoAnalysedClassUses
{
    public const TRAIT_CONSTANT_NOTHING_READS = 1;

    private int $traitPropertyNothingUses;

    public function traitMethodNothingCalls(): void
    {
    }
}
