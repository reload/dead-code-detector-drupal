<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigSchema;

use Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage\WiringRuleTestCase;

final class SchemaCallbackUsageProviderTest extends WiringRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/example/src/schema-callbacks.php'];
    }

    protected static function fixtureNamespace(): string
    {
        return 'Drupal\example\ConfigSchema';
    }

    public static function usedMembers(): iterable
    {
        yield 'Callback constraint callback' => ['Drupal\example\ConfigSchema\SettingsValidator::validateSettings'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'validator method no schema names'
            => ['Unused Drupal\example\ConfigSchema\SettingsValidator::methodNothingCalls'];
    }
}
