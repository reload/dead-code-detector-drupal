<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Settings;

use Reload\DeadCodeDetectorDrupal\Support\Discovery\SettingsFiles;
use PhpParser\Error;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

use function array_unique;
use function array_values;
use function file_get_contents;
use function in_array;
use function strtolower;

/**
 * Lists of strings that settings files assign to $settings, such as
 * $settings['twig_sandbox_allowed_methods'] = ['id', 'label'].
 *
 * Only literal lists are read, not values computed at runtime.
 *
 * @see \Drupal\Core\Site\Settings
 * @see \Drupal\Core\Template\TwigSandboxPolicy
 */
final class SettingLists
{
    /**
     * @var list<string>|null
     *   Contents of the settings files, read on first use.
     */
    private ?array $contents = null;

    public function __construct(
        private readonly SettingsFiles $settingsFiles,
    ) {
    }

    /**
     * @param list<string> $settingNames
     * @return array<string, list<string>>
     *   Setting name => values, for the settings that any file assigns.
     */
    public function lists(array $settingNames): array
    {
        return self::parse($this->contents(), $settingNames);
    }

    /**
     * @return list<string>
     */
    private function contents(): array
    {
        if ($this->contents === null) {
            $this->contents = [];
            foreach ($this->settingsFiles->all() as $filePath) {
                $fileContents = file_get_contents($filePath);
                if ($fileContents !== false) {
                    $this->contents[] = $fileContents;
                }
            }
        }

        return $this->contents;
    }

    /**
     * Which file wins depends on includes and conditions, so the values of
     * every assignment are combined.
     *
     * @param list<string> $contents
     *   Contents of PHP files.
     * @param list<string> $settingNames
     * @return array<string, list<string>>
     *   Setting name => values, for the settings that any file assigns.
     */
    public static function parse(array $contents, array $settingNames): array
    {
        $valuesBySettingName = [];
        foreach ($contents as $fileContents) {
            foreach (self::assignments($fileContents) as [$settingName, $values]) {
                if (in_array($settingName, $settingNames, true)) {
                    $valuesBySettingName[$settingName] = array_values(array_unique(
                        [...$valuesBySettingName[$settingName] ?? [], ...$values],
                    ));
                }
            }
        }

        return $valuesBySettingName;
    }

    /**
     * @return list<array{string, list<string>}>
     *   Setting name and values of each literal assignment or append.
     */
    private static function assignments(string $fileContents): array
    {
        try {
            $statements = (new ParserFactory())->createForHostVersion()->parse($fileContents) ?? [];
        } catch (Error) {
            return [];
        }
        $statements = (new NodeTraverser(new NameResolver()))->traverse($statements);

        $assignments = [];
        foreach ((new NodeFinder())->findInstanceOf($statements, Assign::class) as $assign) {
            $settingName = self::settingName($assign->var);
            if ($settingName !== null) {
                $values = self::literalList($assign->expr);
                if ($values !== null) {
                    $assignments[] = [$settingName, $values];
                }
                continue;
            }

            // $settings['name'][] = 'value';
            if ($assign->var instanceof ArrayDimFetch && $assign->var->dim === null) {
                $settingName = self::settingName($assign->var->var);
                $value = self::literalString($assign->expr);
                if ($settingName !== null && $value !== null) {
                    $assignments[] = [$settingName, [$value]];
                }
            }
        }

        return $assignments;
    }

    /**
     * The name in $settings['name'], if that is what the expression is.
     */
    private static function settingName(Expr $expr): ?string
    {
        if (
            $expr instanceof ArrayDimFetch
            && $expr->var instanceof Variable
            && $expr->var->name === 'settings'
            && $expr->dim instanceof String_
        ) {
            return $expr->dim->value;
        }

        return null;
    }

    /**
     * @return list<string>|null
     *   Null when any item is computed at runtime.
     */
    private static function literalList(Expr $expr): ?array
    {
        if (!$expr instanceof Array_) {
            return null;
        }

        $values = [];
        foreach ($expr->items as $item) {
            $value = self::literalString($item->value);
            if ($value === null) {
                return null;
            }
            $values[] = $value;
        }

        return $values;
    }

    /**
     * A string literal, or a class name written as Foo::class.
     */
    private static function literalString(Expr $expr): ?string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        if (
            $expr instanceof ClassConstFetch
            && $expr->class instanceof Name
            && $expr->name instanceof Identifier
            && strtolower($expr->name->name) === 'class'
        ) {
            return $expr->class->toString();
        }

        return null;
    }
}
