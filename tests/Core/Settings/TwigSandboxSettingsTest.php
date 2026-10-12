<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Settings;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class TwigSandboxSettingsTest extends DeadCodeRuleTestCase
{
    private const VARIABLE_CLASS = 'Drupal\example\Settings\VariableOfSiteReplacingAllowedMethods';

    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/site-replacing-allowed-methods.neon'];
    }

    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/site-replacing-allowed-methods/variables.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'method the site\'s settings allow' => [self::VARIABLE_CLASS . '::methodTheSiteAllows'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'method only core\'s replaced default allows' => ['Unused ' . self::VARIABLE_CLASS . '::label'];
    }
}
