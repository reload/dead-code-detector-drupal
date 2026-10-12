<?php

declare(strict_types=1);

namespace Drupal\example\Access;

use Drupal\Core\Routing\Access\AccessInterface;

final class AccessCheckerCalledByName implements AccessInterface
{
    public function access(): bool
    {
        return true;
    }

    public function helperNothingCalls(): void
    {
    }
}

final class AccessNamedMethodOutsideChecker
{
    public function access(): bool
    {
        return true;
    }
}
