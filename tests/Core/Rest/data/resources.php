<?php

declare(strict_types=1);

namespace Drupal\example\Rest;

use Drupal\rest\Plugin\ResourceBase;

final class ResourceWithHttpVerbMethods extends ResourceBase
{
    public function get(): void
    {
    }

    public function post(): void
    {
    }

    public function Put(): void
    {
    }

    protected function patch(): void
    {
    }

    public function notAnHttpVerb(): void
    {
    }
}

final class VerbNamedMethodOutsideResource
{
    public function get(): void
    {
    }
}
