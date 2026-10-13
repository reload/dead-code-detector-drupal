<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Validation;

use ReflectionClass;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\PropertiesAccessedByNameUsageProvider;
use ShipMonk\PHPStan\DeadCode\Enum\AccessType;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Constraint options, which Symfony's Constraint::__construct() writes to the
 * properties of the same name.
 *
 * @see \Drupal\Core\Validation\ConstraintFactory::createInstance()
 * @see \Symfony\Component\Validator\Constraint::__construct()
 * @see https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/entity-validation-api-overview
 */
final class ConstraintPropertyUsageProvider extends PropertiesAccessedByNameUsageProvider
{
    /**
     * Symfony's base class, whose constructor writes the options.
     */
    protected function typeName(): string
    {
        return Constraint::class;
    }

    /**
     * Written only: the constraint's validator reads them in PHP.
     */
    protected function accessTypes(): array
    {
        return [AccessType::WRITE];
    }

    /**
     * Names Constraint::__construct() as the writer.
     */
    protected function note(): string
    {
        return 'Constraint option, set by Constraint::__construct()';
    }

    /**
     * Drupal's ConstraintFactory passes named arguments to a constructor with
     * #[HasNamedArguments], which sets the properties itself. Otherwise it
     * passes an options array for the base constructor to write, a path
     * Symfony 7.4 deprecates.
     */
    protected function accessesPropertiesOf(ReflectionClass $class): bool
    {
        foreach ($class->getConstructor()?->getAttributes() ?? [] as $attribute) {
            if ($attribute->getName() === HasNamedArguments::class) {
                return false;
            }
        }

        return true;
    }
}
