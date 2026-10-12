<?php

declare(strict_types=1);

namespace Drupal\example\JsonApi;

use Drupal\jsonapi_resources\Resource\ResourceBase;

final class ResourceProcessedByName extends ResourceBase
{
    public function process(): void
    {
    }

    public function helperNothingCalls(): void
    {
    }
}

final class ProcessNamedMethodOutsideResource
{
    public function process(): void
    {
    }
}
