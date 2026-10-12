<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Rest;

use Reload\DeadCodeDetectorDrupal\Tests\Support\Reflection\ByNameRuleTestCase;

final class RestResourceUsageProviderTest extends ByNameRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/resources.php'];
    }

    protected static function fixtureNamespace(): string
    {
        return 'Drupal\example\Rest';
    }

    public static function usedMembers(): iterable
    {
        yield 'GET method of a REST resource' => ['Drupal\example\Rest\ResourceWithHttpVerbMethods::get'];
        yield 'POST method of a REST resource' => ['Drupal\example\Rest\ResourceWithHttpVerbMethods::post'];
        yield 'HTTP verb method declared with capitals' => ['Drupal\example\Rest\ResourceWithHttpVerbMethods::Put'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'non-public HTTP verb method' => ['Unused Drupal\example\Rest\ResourceWithHttpVerbMethods::patch'];
        yield 'resource method named after no verb'
            => ['Unused Drupal\example\Rest\ResourceWithHttpVerbMethods::notAnHttpVerb'];
        yield 'verb-named method outside a resource'
            => ['Unused Drupal\example\Rest\VerbNamedMethodOutsideResource::get'];
    }
}
