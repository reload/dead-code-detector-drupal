<?php

declare(strict_types=1);

namespace Drupal\example\Drush\Factories;

use Drush\Commands\DrushCommands;

final class CommandsBuiltByCreate extends DrushCommands
{
    public static function create(): self
    {
        return new self();
    }
}

final class CommandsBuiltByCreateEarly extends DrushCommands
{
    public static function createEarly(): self
    {
        return new self();
    }
}

final class CommandsBuiltByCreateInOtherCasing extends DrushCommands
{
    public static function CREATE(): self
    {
        return new self();
    }
}

final class CommandsWithInstanceCreate extends DrushCommands
{
    public function create(): void
    {
    }
}

final class CommandsWithProtectedFactory extends DrushCommands
{
    protected static function create(): self
    {
        return new self();
    }
}

final class CommandsWithOtherStaticMethod extends DrushCommands
{
    public static function build(): self
    {
        return new self();
    }
}

final class FactoryOutsideDrushCommands
{
    public static function create(): self
    {
        return new self();
    }
}
