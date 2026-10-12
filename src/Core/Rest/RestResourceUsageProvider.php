<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Rest;

use Drupal\rest\Plugin\ResourceInterface;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\MethodsCalledByNameUsageProvider;

/**
 * REST resource plugins' methods for HTTP methods, which RequestHandler calls
 * by the lowercased request method; ResourceInterface declares none of them.
 *
 * @see \Drupal\rest\RequestHandler
 * @see \Drupal\rest\Plugin\ResourceBase::availableMethods()
 * @see https://www.drupal.org/docs/drupal-apis/restful-web-services-api/restful-web-services-api-overview
 */
final class RestResourceUsageProvider extends MethodsCalledByNameUsageProvider
{
    protected function typeName(): string
    {
        return ResourceInterface::class;
    }

    protected function methodNames(): array
    {
        return ['head', 'get', 'post', 'put', 'delete', 'trace', 'options', 'connect', 'patch'];
    }

    protected function note(): string
    {
        return 'REST resource method for an HTTP method, called by RequestHandler';
    }
}
