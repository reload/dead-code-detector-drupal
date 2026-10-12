<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Callable;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class StringCallableUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/callables.php'];
    }

    public static function usedMembers(): iterable
    {
        yield '"::method" on the form object'
            => ['Drupal\example\Callable\FormWithStringCallbacks::calledByFormObjectString'];
        yield '"Class::method"' => ['Drupal\example\Callable\CallbackTarget::calledByClassString'];
        yield '"service:method" of a declared service'
            => ['Drupal\example\Callable\CallableService::calledByServiceString'];
        yield 'static::class . ":method"'
            => ['Drupal\example\Callable\FormWithStringCallbacks::calledByStaticClassConcatenation'];
        yield 'Foo::class . "::method"' => ['Drupal\example\Callable\CallbackTarget::calledByNamedClassConcatenation'];
        yield '"Class:method" with one colon, which the class resolver also takes'
            => ['Drupal\example\Callable\InstantiatedCallbackHolder::calledBySingleColonLiteral'];
        yield 'constructor of a class the class resolver instantiates for a non-static method'
            => ['Drupal\example\Callable\InstantiatedCallbackHolder::__construct'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield '"service:method" of an undeclared service'
            => ['Unused Drupal\example\Callable\FormWithStringCallbacks::namedOnlyThroughUndeclaredService'];
        yield 'method naming a callback, called by nothing'
            => ['Unused Drupal\example\Callable\CallbackOnlyNamedInDeadCode::deadFormBuilder'];
        yield 'callback named only in dead code'
            => ['Unused Drupal\example\Callable\CallbackOnlyNamedInDeadCode::submitNamedOnlyInDeadCode'];
        yield 'constructor of a class named only for static methods'
            => ['Unused Drupal\example\Callable\CallbackTarget::__construct'];
    }
}
