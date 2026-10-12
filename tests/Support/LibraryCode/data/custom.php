<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\LibraryCode\Data;

final class CustomSubclassOfLibraryClass extends LibraryBaseClass
{
    public const CONSTANT_THE_LIBRARY_READS = 2;

    protected int $propertyTheLibraryUses = 2;

    protected int $propertyTheLibraryWrites;

    public function methodTheLibraryCalls(): void
    {
    }

    public function methodNothingCalls(): void
    {
    }
}

final class CustomImplementationOfLibraryInterface implements LibraryInterface
{
    public function interfaceMethodTheLibraryCalls(): void
    {
    }
}

final class CustomClassUsingLibraryTrait
{
    use LibraryTrait;

    public function traitMethodTheLibraryCalls(): void
    {
    }
}

final class CustomSubclassOfNonLibraryClass extends NonLibraryBaseClass
{
    public function methodOnlyTheParentDeclares(): void
    {
    }
}

trait CustomTraitNoClassUses
{
    public function traitMethodNothingCalls(): void
    {
    }
}
