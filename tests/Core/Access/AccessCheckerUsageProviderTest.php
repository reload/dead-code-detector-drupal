<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Access;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class AccessCheckerUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/access-checkers.php'];
    }

    public static function usedMembers(): iterable
    {
        yield 'access() of an access checker' => ['Drupal\example\Access\AccessCheckerCalledByName::access'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield 'access checker method other than access()'
            => ['Unused Drupal\example\Access\AccessCheckerCalledByName::helperNothingCalls'];
        yield 'access() outside an access checker'
            => ['Unused Drupal\example\Access\AccessNamedMethodOutsideChecker::access'];
    }
}
