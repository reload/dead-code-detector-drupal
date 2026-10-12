<?php

declare(strict_types=1);

namespace Drupal\example\Services;

abstract class ParentWithSetter
{
    public function setFromParent(): void
    {
    }
}

final class ServiceWithInheritedSetter extends ParentWithSetter
{
    public function __construct()
    {
    }

    public function methodNothingCalls(): void
    {
    }
}
