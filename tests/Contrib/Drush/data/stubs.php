<?php

declare(strict_types=1);

namespace Drush\Attributes {
    #[\Attribute(\Attribute::TARGET_METHOD)]
    class Command
    {
        public function __construct(public string $name = '')
        {
        }
    }

    #[\Attribute(\Attribute::TARGET_METHOD)]
    class Hook
    {
        public function __construct(public string $type = '')
        {
        }
    }
}

namespace Drush\Commands {
    abstract class DrushCommands
    {
    }
}
