<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Contrib\JsonApi;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class JsonApiResourceUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/resources.php', __DIR__ . '/data/stubs.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'process() of a resource' => ['Drupal\example\JsonApi\ResourceProcessedByName::process'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'resource method other than process()'
            => ['Unused Drupal\example\JsonApi\ResourceProcessedByName::helperNothingCalls'];
        yield 'process() outside a resource'
            => ['Unused Drupal\example\JsonApi\ProcessNamedMethodOutsideResource::process'];
    }
}
