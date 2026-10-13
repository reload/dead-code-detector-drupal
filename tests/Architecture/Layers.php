<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use ShipMonk\PHPStan\DeadCode\Excluder\MemberUsageExcluder;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;

/**
 * The layering PHPStan checks through phpat: Support knows no Drupal area,
 * Contrib builds on Support alone, and readers come from the container. Only
 * providers and excluders carry their names, which test-mutation skips.
 *
 * Classes are named as strings, so the rules hold from the first commit on.
 */
final class Layers
{
    private const PACKAGE = 'Reload\DeadCodeDetectorDrupal';

    /**
     * Services the container builds from their sources: anything that reads
     * the world, and the readers built on them.
     */
    private const READERS = [
        'Support\Discovery\DrupalFiles',
        'Support\Discovery\SettingsFiles',
        'Support\LibraryCode\LibraryPaths',
        'Core\Services\ServiceDefinitions',
        'Core\Routing\RouteDefinitions',
        'Core\ConfigSchema\SchemaCallbacks',
        'Core\Permission\PermissionCallbacks',
        'Core\Settings\SettingLists',
        'Core\Twig\TwigTemplates',
        'Core\Twig\TwigSandbox',
    ];

    public function testSupportDependsOnNoDrupalArea(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::PACKAGE . '\Support'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace(self::PACKAGE . '\Core'),
                Selector::inNamespace(self::PACKAGE . '\Contrib'),
            )
            ->because('Support is tied to no Drupal API');
    }

    public function testContribBuildsOnSupportAlone(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::PACKAGE . '\Contrib'))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace(self::PACKAGE . '\Core'))
            ->because('contrib modules are covered one at a time, on the shared bases');
    }

    public function testReadersComeFromTheContainer(): Rule
    {
        $readers = [];
        foreach (self::READERS as $reader) {
            $readers[] = Selector::classname(self::PACKAGE . '\\' . $reader);
        }

        return PHPat::rule()
            ->classes(Selector::inNamespace(self::PACKAGE))
            ->excluding(Selector::inNamespace(self::PACKAGE . '\Tests'))
            ->shouldNotConstruct()
            ->classes(...$readers)
            ->because('readers are services the container builds from their sources and injects');
    }

    public function testOnlyProvidersAreNamedUsageProvider(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname('/UsageProvider$/', true))
            ->shouldImplement()
            ->classes(Selector::classname(MemberUsageProvider::class))
            ->because('test-mutation skips files named so, as rule tests alone reach providers');
    }

    public function testOnlyExcludersAreNamedExcluder(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname('/Excluder$/', true))
            ->shouldImplement()
            ->classes(Selector::classname(MemberUsageExcluder::class))
            ->because('test-mutation skips files named so, as rule tests alone reach excluders');
    }
}
