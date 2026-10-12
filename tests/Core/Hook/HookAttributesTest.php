<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Hook;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Core\Hook\HookAttributes;

final class HookAttributesTest extends TestCase
{
    /**
     * @param array<mixed> $arguments
     */
    #[DataProvider('hookNames')]
    public function testHookName(string $attributeName, array $arguments, string $expectedHookName): void
    {
        self::assertSame($expectedHookName, HookAttributes::hookName($attributeName, $arguments));
    }

    /**
     * @return iterable<string, array{string, array<mixed>, string}>
     */
    public static function hookNames(): iterable
    {
        yield 'core hook named by argument name' => [
            'Drupal\Core\Hook\Attribute\Hook',
            ['hook' => 'form_alter'],
            'hook_form_alter',
        ];
        yield 'core class-level hook naming its method' => [
            'Drupal\Core\Hook\Attribute\Hook',
            ['node_insert', 'onNodeInsert'],
            'hook_node_insert',
        ];
        yield 'Hux alter names the altered hook' => ['Drupal\hux\Attribute\Alter', ['mail'], 'hook_mail_alter'];
        yield 'Hux alter named by argument name' => [
            'Drupal\hux\Attribute\Alter',
            ['alter' => 'mail'],
            'hook_mail_alter',
        ];
        yield 'Hux replacement of a module\'s hook' => [
            'Drupal\hux\Attribute\ReplaceOriginalHook',
            ['node_insert', 'node'],
            'hook_node_insert',
        ];
    }
}
