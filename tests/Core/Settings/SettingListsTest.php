<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Settings;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Core\Settings\SettingLists;

final class SettingListsTest extends TestCase
{
    private const METHODS = 'twig_sandbox_allowed_methods';
    private const CLASSES = 'twig_sandbox_allowed_classes';

    /**
     * @param list<string> $contents
     * @param array<string, list<string>> $expectedLists
     */
    #[DataProvider('settingsFiles')]
    public function testReadsLiteralListsOfRequestedSettings(array $contents, array $expectedLists): void
    {
        self::assertSame($expectedLists, SettingLists::parse($contents, [self::METHODS, self::CLASSES]));
    }

    /**
     * @return iterable<string, array{list<string>, array<string, list<string>>}>
     */
    public static function settingsFiles(): iterable
    {
        yield 'list assigned whole' => [
            ["<?php \$settings['twig_sandbox_allowed_methods'] = ['firstMethod', 'secondMethod'];"],
            [self::METHODS => ['firstMethod', 'secondMethod']],
        ];
        yield 'item appended' => [
            ["<?php \$settings['twig_sandbox_allowed_methods'][] = 'appendedMethod';"],
            [self::METHODS => ['appendedMethod']],
        ];
        yield 'class names resolved through use statements' => [
            [<<<'CODE'
                <?php
                use Vendor\Imported\ImportedClass;
                $settings['twig_sandbox_allowed_classes'] = [ImportedClass::class, \Vendor\FullyQualifiedClass::class];
                CODE],
            [self::CLASSES => ['Vendor\Imported\ImportedClass', 'Vendor\FullyQualifiedClass']],
        ];
        yield '::class keyword in any case' => [
            ["<?php \$settings['twig_sandbox_allowed_classes'] = [UppercaseClassKeyword::CLASS];"],
            [self::CLASSES => ['UppercaseClassKeyword']],
        ];
        yield 'lists of several files combined' => [
            [
                "<?php \$settings['twig_sandbox_allowed_methods'] = ['inFirstFileOnly', 'inBothFiles'];",
                "<?php if (getenv('X')) {\n"
                    . "\$settings['twig_sandbox_allowed_methods'] = ['inBothFiles', 'inSecondFileOnly'];\n}",
            ],
            [self::METHODS => ['inFirstFileOnly', 'inBothFiles', 'inSecondFileOnly']],
        ];
        yield 'computed list ignored' => [["<?php \$settings['twig_sandbox_allowed_methods'] = \$computedList;"], []];
        yield 'list with a computed item ignored' => [
            ["<?php \$settings['twig_sandbox_allowed_methods'] = ['literalMethod', \$computedMethod];"],
            [],
        ];
        yield 'computed item appended ignored' => [
            ["<?php \$settings['twig_sandbox_allowed_methods'][] = \$computedMethod;"],
            [],
        ];
        yield 'class constant other than ::class ignored' => [
            ["<?php \$settings['twig_sandbox_allowed_classes'] = [ClassOfConstant::CONSTANT];"],
            [],
        ];
        yield 'setting not asked for ignored' => [
            ["<?php \$settings['setting_not_asked_for'] = ['valueOfOtherSetting'];"],
            [],
        ];
        yield 'other variables ignored' => [
            ["<?php \$config['twig_sandbox_allowed_methods'] = ['methodOfOtherVariable'];"],
            [],
        ];
        yield 'commented-out assignment ignored' => [
            ["<?php // \$settings['twig_sandbox_allowed_methods'] = ['commentedOutMethod'];"],
            [],
        ];
        yield 'file that does not parse ignored' => [["<?php \$settings['twig_sandbox_allowed_methods'] = ["], []];
    }
}
