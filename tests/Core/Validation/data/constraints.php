<?php

declare(strict_types=1);

namespace Drupal\example\Validation;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

final class ConstraintWithoutOwnConstructor extends Constraint
{
    // No defaults in these fixtures: a default value counts as a write.
    public string $optionSetByConstructor;
}

final class ConstraintWithConstructorWithoutNamedArguments extends Constraint
{
    public string $optionSetFromOptionsArray;

    public function __construct(mixed $options = null)
    {
        parent::__construct($options);
    }
}

final class ConstraintTakingNamedArguments extends Constraint
{
    public string $optionNoArgumentSets;

    #[HasNamedArguments]
    public function __construct()
    {
    }
}

final class NotAConstraint
{
    public string $optionNothingWrites;
}

function read_constraint_options(): string
{
    return (new ConstraintWithoutOwnConstructor())->optionSetByConstructor
        . (new ConstraintWithConstructorWithoutNamedArguments())->optionSetFromOptionsArray
        . (new ConstraintTakingNamedArguments())->optionNoArgumentSets
        . (new NotAConstraint())->optionNothingWrites;
}
