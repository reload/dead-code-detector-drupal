<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Contrib\GraphQl;

use Reload\DeadCodeDetectorDrupal\Support\Reflection\MethodsCalledByNameUsageProvider;

/**
 * GraphQL data producers' resolve(), which DataProducerPluginBase calls as
 * [$this, 'resolve'] because its parameters vary per producer.
 *
 * @see https://www.drupal.org/project/graphql
 */
final class GraphQlDataProducerUsageProvider extends MethodsCalledByNameUsageProvider
{
    /**
     * Named as a string, as the GraphQL module may not be installed.
     */
    protected function typeName(): string
    {
        return 'Drupal\graphql\Plugin\DataProducerPluginInterface';
    }

    /**
     * resolve(), whose parameters vary per producer.
     */
    protected function methodNames(): array
    {
        return ['resolve'];
    }

    /**
     * Names resolveField() as the caller.
     */
    protected function note(): string
    {
        return 'GraphQL data producer, resolved by DataProducerPluginBase::resolveField()';
    }
}
