<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection\Ancestry {

    interface InterfaceOfGrandparent
    {
    }

    trait TraitOfTrait
    {
    }

    trait TraitOfParent
    {
        use TraitOfTrait;
    }

    abstract class GrandparentClass implements InterfaceOfGrandparent
    {
    }

    abstract class ParentClass extends GrandparentClass
    {
        use TraitOfParent;
    }

    final class GrandchildClass extends ParentClass
    {
    }
}
