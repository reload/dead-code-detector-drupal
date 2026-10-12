# Writing a usage provider

When Drupal, a contrib module or a library calls code in a way this package does
not know, the detector reports that code as dead. A usage provider closes the
gap: a small PHP class, registered in your PHPStan configuration, that tells
dead-code-detector which members are used and why. This page shows the shapes a
provider takes, how to register and check one, and when the mechanism belongs in
this package instead.

## How dead-code-detector asks

dead-code-detector collects usages while PHPStan analyses each file. A provider
is a service tagged `shipmonk.deadCode.memberUsageProvider` that implements
`ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider`. For every AST node it
may return usages, each naming a member and an origin. A usage whose origin is a
real call site is transitively dead when that code is; a virtual usage, carrying
a note that says why, keeps the member alive on its own.

Most providers need no AST. dead-code-detector's
`ReflectionBasedMemberUsageProvider` asks one question per member of every
analysed class, through `shouldMarkMethodAsUsed()`,
`shouldMarkConstantAsUsed()`, `shouldMarkPropertyAsRead()` and
`shouldMarkPropertyAsWritten()`. Answer with `VirtualUsageData::withNote()` or
null. The note appears in dead-code-detector's debug output, so write the reason
a reader would want: who calls the member, and how.

## Three shapes

### A member marked by an attribute or annotation

A module that calls every method carrying its attribute is the simplest
case, and the one most contrib mechanisms take. Filter the method's
attributes by name, so the attribute class need not be installed:

```php
<?php

namespace MySite\PHPStan;

use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

final class ListenerUsageProvider extends ReflectionBasedMemberUsageProvider
{
    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        return $method->getAttributes('Drupal\some_module\Attribute\Listener') !== []
            ? VirtualUsageData::withNote('Listener, called by some_module')
            : null;
    }
}
```

If the module also instantiates the class, mark its constructor too, as this
package's Drush provider does for command classes: a constructor nothing
calls is reported like any other method.

### Members called by name on subtypes of a type

Core and contrib often call a method on every subclass of a base without
the base declaring it, the way `jsonapi_resources` calls `process()` on each
resource. This package's bases cover that shape with three answers: the
type, the member names, and the note.

```php
<?php

namespace MySite\PHPStan;

use Reload\DeadCodeDetectorDrupal\Support\Reflection\MethodsCalledByNameUsageProvider;

final class WidgetUsageProvider extends MethodsCalledByNameUsageProvider
{
    protected function typeName(): string
    {
        return 'Drupal\some_module\Widget\WidgetBase';
    }

    protected function methodNames(): array
    {
        return ['build', 'submit'];
    }

    protected function note(): string
    {
        return 'Widget, called by some_module\'s WidgetManager';
    }
}
```

The type is matched by name through parents, interfaces and traits, so the
module need not be installed for analysis, and only public methods count.
Method names are compared in lower case.

`PropertiesAccessedByNameUsageProvider` is the same shape for properties a
library reads or writes by name, with `accessTypes()` saying which of
`AccessType::READ` and `AccessType::WRITE` apply, and an optional
`accessesPropertiesOf()` to narrow the classes, as the constraint provider
does for constraints without named arguments. Private properties never
count, because the library accesses them from its own scope.

The package is not at 1.0 yet, so pin the version you build on.

### A caller visible in PHP code

When the call is in PHP but dead-code-detector cannot follow it, implement
`MemberUsageProvider` directly and read the AST. Type the receiver with the
scope, emit a `ClassMethodUsage` whose origin is
`UsageOrigin::createRegular($node, $scope)`, and the usage stays
transitively dead when its caller is. dead-code-detector's README shows this for
Symfony's `deserialize()`, which constructs the class named in its second
argument. This package's string-callable provider is a larger example: it
resolves `'Class::method'` strings through PHPStan's reflection and the
service wiring.

## Registering the provider

PHPStan builds its services before it loads `bootstrapFiles`, so the class
must be autoloadable through Composer. A `phpstan/` directory in the project
with an `autoload-dev` entry works well:

```json
{
    "autoload-dev": {
        "psr-4": {
            "MySite\\PHPStan\\": "phpstan/"
        }
    }
}
```

Then tag the service in the PHPStan configuration:

```neon
services:
    -
        class: MySite\PHPStan\WidgetUsageProvider
        tags:
            - shipmonk.deadCode.memberUsageProvider
```

## Checking that it works

Use dead-code-detector's [debugging][dcd-debugging]: list a member you expect
to be alive under `shipmonkDeadCode.debug.usagesOf`, run PHPStan with `-vvv`,
and the provider's note shows up on the usage when it fired.

Changing the provider's code does not invalidate PHPStan's result cache:
PHPStan tracks the analysed files and its configuration, not the classes its
services are built from. Run `phpstan clear-result-cache` after editing a
provider.

## When it belongs in this package

If the mechanism is Drupal core's, or a contrib module's that others use,
contribute it here rather than keeping it in a site. The
[coverage map](coverage.md) is the backlog: each row names what reaches a
member and what covers it, and the rows marked not covered are waiting for a
provider. Two kinds of mechanism cannot be done well from a site:

- **Wiring in YAML files this package does not read**, such as migrations
  or exported configuration. A provider for them needs a reader that the
  result cache knows about, or PHPStan serves stale results when the files
  change. The package's `DeclaredUsageProvider` base, a reader, and a
  result-cache entry are how the services, routes and schema providers are
  built; `src/Core/Permission` is the smallest complete example.
- **Anything that depends on the analysed and excluded paths**, which only
  the package's discovery sees.

[AGENTS.md](../AGENTS.md) lists what a provider in this package needs: a
directory per mechanism, documentation references, a coverage row, and tests
named after the cases they prove.

[dcd-debugging]: https://github.com/shipmonk-rnd/dead-code-detector#debugging
