<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Callable;

use Reload\DeadCodeDetectorDrupal\Tests\DeadCodeRuleTestCase;

final class StringCallableUsageProviderTest extends DeadCodeRuleTestCase
{
    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/data/drupal-files.neon'];
    }

    protected static function fixtureFiles(): array
    {
        return [__DIR__ . '/data/callables.php'];
    }

    public static function usedMembers(): iterable
    {
        yield '"::method" on the form object' => ['Drupal\example\FormWithStringCallbacks::calledByFormObjectString'];
        yield '"Class::method"' => ['Drupal\example\CallbackTarget::calledByClassString'];
        yield '"service:method" of a declared service' => ['Drupal\example\CallableService::calledByServiceString'];
        yield 'static::class . ":method"'
            => ['Drupal\example\FormWithStringCallbacks::calledByStaticClassConcatenation'];
        yield 'Foo::class . "::method"' => ['Drupal\example\CallbackTarget::calledByNamedClassConcatenation'];
        yield '"Class:method" with one colon, which the class resolver also takes'
            => ['Drupal\example\InstantiatedCallbackHolder::calledBySingleColonLiteral'];
        yield 'constructor of a class the class resolver instantiates for a non-static method'
            => ['Drupal\example\InstantiatedCallbackHolder::__construct'];
    }

    public static function deadMemberMessages(): iterable
    {
        yield '"service:method" of an undeclared service'
            => ['Unused Drupal\example\FormWithStringCallbacks::namedOnlyThroughUndeclaredService'];
        yield 'method naming a callback, called by nothing'
            => ['Unused Drupal\example\CallbackOnlyNamedInDeadCode::deadFormBuilder'];
        yield 'callback named only in dead code'
            => ['Unused Drupal\example\CallbackOnlyNamedInDeadCode::submitNamedOnlyInDeadCode'];
        yield 'constructor of a class named only for static methods'
            => ['Unused Drupal\example\CallbackTarget::__construct'];
    }
}
