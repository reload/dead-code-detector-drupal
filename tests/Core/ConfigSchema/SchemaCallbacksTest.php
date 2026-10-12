<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigSchema;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Core\ConfigSchema\SchemaCallbacks;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;
use Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage\DeclaredMethods;

final class SchemaCallbacksTest extends TestCase
{
    public function testCallbackConstraintAsListIsCalled(): void
    {
        $usages = self::parse(<<<'YAML'
            example.settings:
              type: config_object
              constraints:
                Callback:
                  callback: ['\Drupal\example\SettingsValidator', validateList]
            YAML);

        self::assertSame(['validateList'], DeclaredMethods::of($usages, 'Drupal\example\SettingsValidator'));
    }

    public function testCallbackConstraintAsStringIsCalled(): void
    {
        $usages = self::parse(<<<'YAML'
            example.settings:
              type: config_object
              mapping:
                label:
                  type: label
                  constraints:
                    Callback:
                      callback: 'Drupal\example\LabelValidator::validateString'
            YAML);

        self::assertSame(['validateString'], DeclaredMethods::of($usages, 'Drupal\example\LabelValidator'));
    }

    public function testCallbackConstraintGivenAsItsDefaultOptionIsCalled(): void
    {
        $usages = self::parse(<<<'YAML'
            example.settings:
              type: config_object
              constraints:
                Callback: ['\Drupal\example\SettingsValidator', validateDefaultOption]
            YAML);

        self::assertSame(['validateDefaultOption'], DeclaredMethods::of($usages, 'Drupal\example\SettingsValidator'));
    }

    public function testCallbacksAtEveryDepthOfOneFileAreCalled(): void
    {
        $usages = self::parse(<<<'YAML'
            example.settings:
              type: config_object
              constraints:
                Callback:
                  callback: ['\Drupal\example\SettingsValidator', validateSettings]
              mapping:
                label:
                  type: label
                  constraints:
                    Callback:
                      callback: ['\Drupal\example\SettingsValidator', validateLabel]
            YAML);

        self::assertSame(
            ['validateLabel', 'validateSettings'],
            DeclaredMethods::of($usages, 'Drupal\example\SettingsValidator'),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedCallbackLists(): iterable
    {
        yield 'list of one item' => ['[Drupal\example\SettingsValidator]'];
        yield 'list whose first item is no string' => ['[123, validate]'];
    }

    #[DataProvider('malformedCallbackLists')]
    public function testMalformedCallbackListDeclaresNoMethod(string $callback): void
    {
        $usages = self::parse(<<<YAML
            example.settings:
              type: config_object
              constraints:
                Callback:
                  callback: $callback
            YAML);

        self::assertTrue($usages->isEmpty());
    }

    public function testFunctionCallbackDeclaresNoMethod(): void
    {
        $usages = self::parse(<<<'YAML'
            example.settings:
              type: config_object
              constraints:
                Callback:
                  callback: example_validate_function
            YAML);

        self::assertTrue($usages->isEmpty());
    }

    private static function parse(string $yaml): DeclaredUsages
    {
        return SchemaCallbacks::parse(['/site/example/config/schema/example.schema.yml' => $yaml]);
    }
}
