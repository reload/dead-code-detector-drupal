<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Validation;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class ConstraintPropertyUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/constraints.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'option of a constraint without a constructor of its own'
            => ['Drupal\example\Validation\ConstraintWithoutOwnConstructor::$optionSetByConstructor'];
        yield 'option of a constraint whose constructor takes an options array'
            => ['Drupal\example\Validation\ConstraintWithConstructorWithoutNamedArguments::$optionSetFromOptionsArray'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'property of a constraint taking named arguments that none sets'
            => ['Property Drupal\example\Validation\ConstraintTakingNamedArguments::$optionNoArgumentSets'
                . ' is never written'];
        yield 'property of a class that is no constraint'
            => ['Property Drupal\example\Validation\NotAConstraint::$optionNothingWrites is never written'];
    }
}
