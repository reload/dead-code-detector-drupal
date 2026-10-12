<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\ClassAncestry;
use Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection\Ancestry\GrandchildClass;

use function sort;

final class ClassAncestryTest extends TestCase
{
    public function testTypeNamesIncludeEveryAncestorInterfaceAndTrait(): void
    {
        $typeNames = ClassAncestry::typeNames(new ReflectionClass(GrandchildClass::class));
        sort($typeNames);

        $namespace = 'Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection\Ancestry\\';
        self::assertSame(
            [
                $namespace . 'GrandparentClass',
                $namespace . 'InterfaceOfGrandparent',
                $namespace . 'ParentClass',
                $namespace . 'TraitOfParent',
                $namespace . 'TraitOfTrait',
            ],
            $typeNames,
        );
    }
}
