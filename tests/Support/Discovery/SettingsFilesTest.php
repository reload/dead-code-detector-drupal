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
        self::assertSame(
            self::SETTINGS_FILES,
            SettingsFiles::besideCore(self::DRUPAL_ROOT . '/vendor/composer/../../core')->all(),
        );
    }

    public function testFindsNothingWhileTwigMatchingIsOff(): void
    {
        $corePath = self::DRUPAL_ROOT . '/vendor/composer/../../core';

        self::assertSame([], SettingsFiles::forCore($corePath, twigTemplatesEnabled: false)->all());
    }

    public function testFindsNothingWithoutCore(): void
    {
        self::assertSame([], SettingsFiles::forCore(null, twigTemplatesEnabled: true)->all());
    }

    public function testFindsNothingBesideCoreWithoutSites(): void
    {
        self::assertSame([], SettingsFiles::besideCore(__DIR__ . '/data/root-without-sites/core')->all());
    }
}
