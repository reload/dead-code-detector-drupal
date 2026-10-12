<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Twig;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class TwigTemplatesDisabledTest extends DeadCodeRuleTestCase
{
    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/disabled/drupal-files.neon', __DIR__ . '/data/twig-disabled.neon'];
    }

    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/disabled/variables.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'method called from PHP' => ['Drupal\example\TwigDisabled\TemplateVariable::calledFromPhp'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'getter only a template calls'
            => ['Unused Drupal\example\TwigDisabled\TemplateVariable::getShownByTemplate'];
    }
}
