<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigSchema;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class SchemaCallbackUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/example/src/schema-callbacks.php'];
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
