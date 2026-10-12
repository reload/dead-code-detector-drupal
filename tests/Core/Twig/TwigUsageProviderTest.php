<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Twig;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class TwigUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/drupal-files.neon'];
    }

    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/variables.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'method on the sandbox allow list' => ['Drupal\example\TemplateVariable::label'];
        yield '"is" method by attribute name' => ['Drupal\example\TemplateVariable::isPublished'];
        yield 'public property' => ['Drupal\example\TemplateVariable::$propertyRead'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'method the sandbox blocks' => ['Unused Drupal\example\TemplateVariable::notAllowedBySandbox'];
        yield 'protected method' => ['Unused Drupal\example\TemplateVariable::getProtectedAccess'];
        yield 'protected property'
            => ['Property Drupal\example\TemplateVariable::$protectedPropertyAccess is never read'];
    }
}
