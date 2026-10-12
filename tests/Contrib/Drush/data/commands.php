<?php

declare(strict_types=1);

namespace Drupal\example\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

final class DrushCommandsSubclass extends DrushCommands
{
    public function __construct()
    {
    }

    #[CLI\Command(name: 'example:attributed')]
    public function commandByAttribute(): void
    {
    }

    /**
     * @command example:annotated
     */
    public function commandByAnnotation(): void
    {
    }

    #[CLI\Hook(type: 'pre-command')]
    public function hookByAttribute(): void
    {
    }

    /**
     * @hook pre-command example:annotated
     */
    public function hookByAnnotation(): void
    {
    }

    public function helperNothingCalls(): void
    {
    }
}

final class NotADrushCommandsSubclass
{
    public function __construct()
    {
    }
}

final class CommandsBuiltByCreate extends DrushCommands
{
    public static function create(): self
    {
        return new self();
    }
}
