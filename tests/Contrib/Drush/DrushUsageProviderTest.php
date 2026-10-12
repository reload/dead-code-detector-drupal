<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Contrib\Drush;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class DrushUsageProviderTest extends DeadCodeRuleTestCase
{
    private const COMMANDS = 'Drupal\example\Drush\Commands\DrushCommandsSubclass';

    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/config.neon'];
    }

    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/commands.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'command by attribute' => [self::COMMANDS . '::commandByAttribute'];
        yield 'command by annotation' => [self::COMMANDS . '::commandByAnnotation'];
        yield 'hook by attribute' => [self::COMMANDS . '::hookByAttribute'];
        yield 'hook by annotation' => [self::COMMANDS . '::hookByAnnotation'];
        yield 'constructor of DrushCommands subclass' => [self::COMMANDS . '::__construct'];
        yield 'static create() of DrushCommands subclass'
            => ['Drupal\example\Drush\Commands\CommandsBuiltByCreate::create'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'method neither command nor hook' => ['Unused ' . self::COMMANDS . '::helperNothingCalls'];
        yield 'constructor of class outside DrushCommands'
            => ['Unused Drupal\example\Drush\Commands\NotADrushCommandsSubclass::__construct'];
    }
}
