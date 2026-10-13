<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\Discovery;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\SettingsFiles;

use function array_keys;

final class SettingsFilesTest extends TestCase
{
    private const DRUPAL_ROOT = __DIR__ . '/data/drupal-root';

    private const SETTINGS_FILES = [
        self::DRUPAL_ROOT . '/sites/default/included.settings.php',
        self::DRUPAL_ROOT . '/sites/default/settings.php',
        self::DRUPAL_ROOT . '/sites/second/settings.php',
    ];

    public function testFindsPhpFilesDirectlyInEachSiteDirectoryExceptTheTemplate(): void
    {
        self::assertSame(self::SETTINGS_FILES, SettingsFiles::inDrupalRoot(self::DRUPAL_ROOT)->all());
    }

    public function testReadsTheContentsOfEachFileItFound(): void
    {
        $contentsByFilePath = SettingsFiles::inDrupalRoot(self::DRUPAL_ROOT)->read();

        self::assertSame(self::SETTINGS_FILES, array_keys($contentsByFilePath));
        foreach ($contentsByFilePath as $contents) {
            self::assertStringStartsWith('<?php', $contents);
        }
    }

    public function testFindsSitesBesideCoreAtComposersInstallPath(): void
    {
        $corePath = self::DRUPAL_ROOT . '/vendor/composer/../../core';

        self::assertSame(self::SETTINGS_FILES, SettingsFiles::forCore($corePath, twigTemplatesEnabled: true)->all());
    }

    public function testFindsNothingBesideCoreWithoutSites(): void
    {
        self::assertSame(
            [],
            SettingsFiles::forCore(__DIR__ . '/data/root-without-sites/core', twigTemplatesEnabled: true)->all(),
        );
    }

    public function testDrupalRootIsAboveCoreWithoutResolvingSymlinks(): void
    {
        self::assertSame('/site', SettingsFiles::drupalRoot('/site/vendor/composer/../../core', true));
    }

    public function testThereIsNoDrupalRootToSearchWhileTwigMatchingIsOff(): void
    {
        self::assertNull(SettingsFiles::drupalRoot('/site/core', false));
    }

    public function testThereIsNoDrupalRootWithoutCore(): void
    {
        self::assertNull(SettingsFiles::drupalRoot(null, true));
    }
}
