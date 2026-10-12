<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Contrib\GraphQl;

use Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection\ByNameRuleTestCase;

final class GraphQlDataProducerUsageProviderTest extends ByNameRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/data-producers.php', __DIR__ . '/data/stubs.php'];
    }

    protected static function fixtureNamespace(): string
    {
        return 'Drupal\example\GraphQl';
    }

    public static function usedMembers(): iterable
    {
        yield 'resolve() of a data producer' => ['Drupal\example\GraphQl\DataProducerResolvedByName::resolve'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'data producer method other than resolve()'
            => ['Unused Drupal\example\GraphQl\DataProducerResolvedByName::helperNothingCalls'];
        yield 'resolve() outside a data producer'
            => ['Unused Drupal\example\GraphQl\ResolveNamedMethodOutsideDataProducer::resolve'];
    }
}
