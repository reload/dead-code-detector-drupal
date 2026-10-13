<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Hook;

use Drupal\Core\Hook\Attribute\Hook;

use function array_key_exists;
use function is_string;
use function sprintf;

/**
 * Attributes that make Drupal call a method as a hook: core's #[Hook] (Drupal
 * 11.1+) and those of Hux, the contrib module that offered OOP hooks first.
 *
 * @see \Drupal\Core\Hook\Attribute\Hook
 * @see https://www.drupal.org/node/3442349
 * @see https://www.drupal.org/project/hux
 */
final class HookAttributes
{
    private const CORE_HOOK = Hook::class;

    /**
     * Method attributes, and how each names the hook it implements.
     */
    private const HOOK_NAME_FORMATS = [
        self::CORE_HOOK => 'hook_%s',
        'Drupal\hux\Attribute\Hook' => 'hook_%s',
        'Drupal\hux\Attribute\Alter' => 'hook_%s_alter',
        'Drupal\hux\Attribute\ReplaceOriginalHook' => 'hook_%s',
    ];

    /**
     * Whether the attribute makes a method a hook implementation.
     *
     * @phpstan-pure
     */
    public static function isMethodHook(string $attributeName): bool
    {
        return array_key_exists($attributeName, self::HOOK_NAME_FORMATS);
    }

    /**
     * Only core's attribute also targets classes.
     *
     * @phpstan-pure
     */
    public static function isClassHook(string $attributeName): bool
    {
        return $attributeName === self::CORE_HOOK;
    }

    /**
     * The hook the attribute implements, for the usage's note; "?" where its
     * arguments do not say.
     *
     * @param array<mixed> $arguments
     *   The attribute's arguments, keyed by name or position.
     * @phpstan-pure
     */
    public static function hookName(string $attributeName, array $arguments): string
    {
        $hookName = $arguments['hook'] ?? $arguments['alter'] ?? $arguments[0] ?? '?';
        $format = self::HOOK_NAME_FORMATS[$attributeName] ?? 'hook_%s';

        return sprintf($format, is_string($hookName) ? $hookName : '?');
    }
}
