<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Contrib\JsonApi;

use Reload\DeadCodeDetectorDrupal\Support\Reflection\MethodsCalledByNameUsageProvider;

/**
 * JSON:API Resources' process(), which JsonapiResourceController calls with
 * arguments resolved per resource, so ResourceBase does not declare it.
 *
 * @see https://www.drupal.org/project/jsonapi_resources
 */
final class JsonApiResourceUsageProvider extends MethodsCalledByNameUsageProvider
{
    protected function typeName(): string
    {
        return 'Drupal\jsonapi_resources\Resource\ResourceBase';
    }

    protected function methodNames(): array
    {
        return ['process'];
    }

    protected function note(): string
    {
        return 'JSON:API resource, called by JsonapiResourceController';
    }
}
