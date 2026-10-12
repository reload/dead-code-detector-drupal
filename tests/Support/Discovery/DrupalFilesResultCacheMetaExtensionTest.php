<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\Discovery;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFiles;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFilesResultCacheMetaExtension;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\SettingsFiles;
use Symfony\Component\Filesystem\Filesystem;

use function sys_get_temp_dir;
use function uniqid;

/**
 * Reading files is the thing under test: a changed or moved file changes the
 * hash, a relocated checkout does not.
 */
final class DrupalFilesResultCacheMetaExtensionTest extends TestCase
{
    private string $directory;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->directory = sys_get_temp_dir() . '/' . uniqid('drupal-files-', true);
        $this->filesystem->mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->directory);
    }

    public function testHashChangesWhenAWiringFileChangesAtTheSameLength(): void
    {
        $wiringFile = $this->directory . '/example.routing.yml';
        $this->filesystem->dumpFile($wiringFile, "example.page: {}\n");
        $hashBefore = self::hash(new DrupalFiles([$this->directory]), new SettingsFiles([]));

        $this->filesystem->dumpFile($wiringFile, "example.edit: {}\n");

        self::assertNotSame($hashBefore, self::hash(new DrupalFiles([$this->directory]), new SettingsFiles([])));
    }

    public function testHashChangesWhenASettingsFileChangesAtTheSameLength(): void
    {
        $settingsFile = $this->directory . '/settings.php';
        $this->filesystem->dumpFile($settingsFile, "<?php \$settings['a'] = 1;\n");
        $hashBefore = self::hash(new DrupalFiles([]), new SettingsFiles([$settingsFile]));

        $this->filesystem->dumpFile($settingsFile, "<?php \$settings['b'] = 1;\n");

        self::assertNotSame($hashBefore, self::hash(new DrupalFiles([]), new SettingsFiles([$settingsFile])));
    }

    public function testHashChangesWhenAWiringFileMovesIntoTests(): void
    {
        $this->filesystem->dumpFile($this->directory . '/example/example.routing.yml', "example.page: {}\n");
        $hashBefore = self::hash(new DrupalFiles([$this->directory]), new SettingsFiles([]));

        $this->filesystem->mkdir($this->directory . '/example/tests/modules/example_test');
        $this->filesystem->rename(
            $this->directory . '/example/example.routing.yml',
            $this->directory . '/example/tests/modules/example_test/example.routing.yml',
        );

        self::assertNotSame($hashBefore, self::hash(new DrupalFiles([$this->directory]), new SettingsFiles([])));
    }

    public function testHashIsTheSameForTheSameFilesInAnotherCheckout(): void
    {
        $this->filesystem->dumpFile($this->directory . '/checkout/example/example.routing.yml', "example.page: {}\n");
        $this->filesystem->dumpFile(
            $this->directory . '/moved-checkout/example/example.routing.yml',
            "example.page: {}\n",
        );

        self::assertSame(
            self::hash(new DrupalFiles([$this->directory . '/checkout']), new SettingsFiles([])),
            self::hash(new DrupalFiles([$this->directory . '/moved-checkout']), new SettingsFiles([])),
        );
    }

    private static function hash(DrupalFiles $drupalFiles, SettingsFiles $settingsFiles): string
    {
        return (new DrupalFilesResultCacheMetaExtension($drupalFiles, $settingsFiles))->getHash();
    }
}
