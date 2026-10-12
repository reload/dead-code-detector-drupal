<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\LibraryCode;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class LibraryCodeUsageProviderTest extends DeadCodeRuleTestCase
{
    private const FIXTURES = 'Reload\DeadCodeDetectorDrupal\Tests\Support\LibraryCode\Data';

    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/library-paths.neon'];
    }

    protected static function fixtureFiles(): array
    {
        return [
            __DIR__ . '/data/custom.php',
            __DIR__ . '/data/library/LibraryClassInPaths.php',
            __DIR__ . '/data/library/LibraryEnumInPaths.php',
            __DIR__ . '/data/library/LibraryTraitNoAnalysedClassUses.php',
        ];
    }

    public static function usedMembers(): iterable
    {
        $subclass = self::FIXTURES . '\CustomSubclassOfLibraryClass';
        yield 'method overriding library class' => [$subclass . '::methodTheLibraryCalls'];
        yield 'constant overriding library class' => [$subclass . '::CONSTANT_THE_LIBRARY_READS'];
        yield 'property with default overriding library class' => [$subclass . '::$propertyTheLibraryUses'];
        yield 'property without default overriding library class' => [$subclass . '::$propertyTheLibraryWrites'];
        yield 'method implementing library interface'
            => [self::FIXTURES . '\CustomImplementationOfLibraryInterface::interfaceMethodTheLibraryCalls'];
        yield 'method overriding library trait method'
            => [self::FIXTURES . '\CustomClassUsingLibraryTrait::traitMethodTheLibraryCalls'];
        yield 'analysed library method' => [self::FIXTURES . '\LibraryClassInPaths::methodNothingCalls'];
        yield 'analysed library constant' => [self::FIXTURES . '\LibraryClassInPaths::CONSTANT_NOTHING_READS'];
        yield 'analysed library property' => [self::FIXTURES . '\LibraryClassInPaths::$propertyNothingUses'];
        yield 'analysed library enum case' => [self::FIXTURES . '\LibraryEnumInPaths::CaseNothingUses'];
        $trait = self::FIXTURES . '\LibraryTraitNoAnalysedClassUses';
        yield 'method of a library trait no analysed class uses' => [$trait . '::traitMethodNothingCalls'];
        yield 'constant of a library trait no analysed class uses' => [$trait . '::TRAIT_CONSTANT_NOTHING_READS'];
        yield 'property of a library trait no analysed class uses' => [$trait . '::$traitPropertyNothingUses'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'method overriding nothing'
            => ['Unused ' . self::FIXTURES . '\CustomSubclassOfLibraryClass::methodNothingCalls'];
        yield 'method overriding non-library class'
            => ['Unused ' . self::FIXTURES . '\CustomSubclassOfNonLibraryClass::methodOnlyTheParentDeclares'];
        yield 'method of a custom trait no class uses'
            => ['Unused ' . self::FIXTURES . '\CustomTraitNoClassUses::traitMethodNothingCalls'];
    }
}
