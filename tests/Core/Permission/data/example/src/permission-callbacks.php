<?php

declare(strict_types=1);

namespace Drupal\example\Permission;

final class ClassWithPermissionCallback
{
    public function __construct()
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function permissions(): array
    {
        return [];
    }

    public function methodNothingCalls(): void
    {
    }
}
