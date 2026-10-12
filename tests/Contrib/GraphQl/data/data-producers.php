<?php

declare(strict_types=1);

namespace Drupal\example\GraphQl;

use Drupal\graphql\Plugin\DataProducerPluginInterface;

final class DataProducerResolvedByName implements DataProducerPluginInterface
{
    public function resolve(): void
    {
    }

    public function helperNothingCalls(): void
    {
    }
}

final class ResolveNamedMethodOutsideDataProducer
{
    public function resolve(): void
    {
    }
}
