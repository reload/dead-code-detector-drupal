# Dead code detector for Drupal

Drupal support for [shipmonk/dead-code-detector][dcd], the PHPStan extension
that reports class members nothing uses: methods, constants, enum cases, and
properties that are never read or never written. On a Drupal site it drowns
in false positives, because Drupal calls code through YAML, attributes,
templates and conventions it does not know. This package adds the usage
providers that teach it those mechanisms, so what remains in the report is
dead code in your own modules, themes and profiles.

## Installation

The package covers Drupal 11.3 and later on PHP 8.3 and later. Drupal 10 is
out of scope: its `drupal/core-dev` requires PHPStan 1, and shipmonk needs
PHPStan 2.

```sh
composer require --dev reload/dead-code-detector-drupal
```

This brings [phpstan-drupal][phpstan-drupal], which the detector needs.
Without it, PHPStan analyses no `.module`, `.theme`, `.install` or `.inc`
files, so code that only procedural hooks call is reported dead. Nor can it
load core modules' and contrib's classes, so members that override theirs are
reported dead too.

With [phpstan/extension-installer][extension-installer], that is all.
Without it, include the extensions in your PHPStan config:

```neon
includes:
    - vendor/mglaman/phpstan-drupal/extension.neon
    - vendor/shipmonk/dead-code-detector/rules.neon
    - vendor/reload/dead-code-detector-drupal/extension.neon
```

**Analyse all your custom code.** PHPStan's `paths` should include every
custom module, theme and profile, tests included. Code left out of the
analysed paths does not count as library code. Its callers are invisible, and
overrides of its members can be reported as dead.

**Map your custom entity types for phpstan-drupal.** The detector only
follows calls whose target PHPStan can type, and for Drupal that typing comes
from phpstan-drupal, which only knows core's entity types. For your own, calls
on loaded entities have no known type, and the detector then has to guess:

- **By default**, a call like `$entity->foo()` keeps every method named `foo`
  alive, which hides dead code.
- **With shipmonk's `usageOverMixed` excluder**, it reports false positives
  instead.

Map your entity types:

```neon
parameters:
    drupal:
        entityMapping:
            my_entity:
                class: Drupal\my_module\Entity\MyEntity
```

## What counts as used

shipmonk reports class members that nothing uses. This package tells it which
code is yours, and how Drupal reaches that code.

**Only custom code is reported.** Drupal core, contrib modules and themes,
and everything else Composer installs is library code, wherever Composer put
it: `web/core`, `web/modules/contrib` or `vendor/`. shipmonk alone treats only
`vendor/` that way. Library code is never reported, and a member that
overrides or implements a library member counts as used, such as a Views
field plugin's `render()` or a migrate process plugin's `transform()`.

**Tests do not keep code alive.** Usages from a `tests` directory do not keep
code outside tests alive, whether PHP calls, a test module's YAML or a test
theme's templates make them. The report says when that is the only reason a
member is dead. Test code itself is still analysed.

A member counts as used when Drupal reaches it through any of these:

- **Hooks**: `#[Hook]` on a method, or on a class with `method:` or
  `__invoke`, in modules and themes; Hux's `#[Hook]`, `#[Alter]` and
  `#[ReplaceOriginalHook]`; and the constructors of hook classes. Procedural
  hooks need nothing: shipmonk does not report functions, and every function
  body is an entry point, so whatever a function calls counts as used.
- **Services** (`*.services.yml`): constructors, autowired services included;
  factories, `calls` and configurators; and the methods core calls through
  the `access_check`, `cache.context` and `service_collector` tags.
- **Routes**: in `*.routing.yml`, controllers written as `Class::method`,
  `service:method` or an invokable class, title callbacks, `_custom_access`,
  forms and `route_callbacks`, with the constructors of the classes Drupal
  builds for them. With `#[Route]` on a controller (Drupal 11.4+), the
  constructor; shipmonk itself counts the routed methods.
- **Plugins, entities and validation**: plugin attribute properties, which
  `AttributeBase::get()` reads; config entity properties, which Drupal writes
  on load and reads on save; `#[ActionMethod]` methods of config entities,
  which recipes call; constraint properties set from an options array; and
  config schema `Callback` constraints.
- **Drush**: commands and hooks, by attribute or annotation, and the
  constructors of command classes.
- **String callables**: `'::method'`, `'Class::method'`, `'Class:method'` and
  `'service:method'`, written out or built with `Foo::class . '::method'`,
  plus the constructor when Drupal instantiates the class for the call. They
  count as used from where they appear, so a callback named only by dead code
  is dead too.
- **Twig templates**: names that templates under the analysed paths access,
  limited to the methods Drupal's Twig sandbox lets templates call. See
  [Twig templates](docs/twig.md) for what this covers and misses.
- **Calls by name**: members that core and contrib call by name on subtypes
  of a type that does not declare them: REST resources' HTTP methods,
  JSON:API resources' `process()`, GraphQL data producers' `resolve()`,
  access checkers' `access()`, and the `on404`-style handlers of HTTP
  exception subscribers.
- **shipmonk's built-ins**, which switch on in any Drupal site: Symfony event
  subscribers, `#[Required]` setters, `#[Route]` methods and
  `#[AsMessageHandler]` handlers; Twig extension callbacks; PHPUnit tests
  and data providers; Behat contexts.

[Coverage](docs/coverage.md) lists Drupal's documented mechanisms one by one,
with what covers each.

## Configuration

These are the defaults. Your own config overrides them:

```neon
parameters:
    drupalDeadCode:
        twigTemplates:
            # Switch off to stop templates keeping members alive.
            enabled: true
        testsExcluder:
            # Switch off to let usages in tests keep code alive.
            enabled: true
```

Those two switches are the whole configuration, and that is a design choice
rather than an omission:

- **Everything else is read from the site.** The services, routes, schema and
  permissions come from the YAML under the analysed paths. The Twig sandbox's
  `twig_sandbox_allowed_classes`, `_methods` and `_prefixes` come from the PHP
  files in your Drupal root's `sites/*/` directories, read statically, with
  core's defaults as the fallback. Nothing is declared twice, so nothing can
  drift from what the site does.
- **Contrib modules are covered one at a time**, each with a provider that
  knows how that module calls code. A list of attribute or class names could
  not say that, so there is no such list to configure.
- **Site-specific callers get a provider in the site**, or an `@api` tag. See
  [when the detector cannot see the caller](#when-the-detector-cannot-see-the-caller).

shipmonk's own parameters still apply, and this package cannot change their
defaults: extension-installer loads shipmonk's rules after this package's, so
switching off a kind of finding is yours to do.
`shipmonkDeadCode.usageExcluders.usageOverMixed.enabled` is the one most sites
want to decide on.

## Limitations

### By design

- **Wiring is read statically.** Drupal is not bootstrapped and no database
  is needed. Wiring done in PHP rather than YAML, such as a service
  provider's `register()`, is seen only where it names a method as a string
  callable.
- **Templates are matched by name.** A template does not say what class a
  variable holds, so a public member of any analysed class counts as used if
  a template accesses its name and the sandbox allows it. A coincidental name
  keeps a dead member alive, and a sandbox list computed at runtime is not
  seen. [Twig templates](docs/twig.md) records what this costs: about one
  hidden finding per site.
- **When unsure, code is used.** A call on a value PHPStan cannot type keeps
  every member of that name alive. That is shipmonk's default, kept because a
  report is something people act on by deleting code.
- **Only custom code is reported.** There is no mode for contrib modules,
  where every public member is potential API, and no reporting of dead
  functions, which shipmonk does not analyse.
- **Tests are a directory named `tests`** below an analysed path. A test
  module elsewhere counts as production code. shipmonk's own tests excluder
  works from `autoload-dev`, which Drupal sites do not declare, so the
  directory name is the only signal there is.

### Not covered yet

The [coverage map](docs/coverage.md#not-covered) keeps the current list. The
costliest gap is constructors reached only through core's
`AutowireTrait::create()`, such as forms built with `getForm()` or validators
with injected services, when nothing this package reads names the class.

### Callers outside Drupal's wiring

Symfony's serializer constructing input objects, graphql-php reading response
properties by name, and a `method_exists()` check before a call are invisible
to shipmonk and to this package alike. Mark such classes with `@api`, or write
a provider for the library.

### In shipmonk

A property that a trait, the class using it or that class's parent declares
again is reported as unused, and a subclass redeclaring a property with a
default does not count as writing it. Both are shipmonk limitations the
measurements found.

## When the detector cannot see the caller

Use shipmonk's built-in mechanisms:

- **`@api`** on a member marks it and its overrides as used. On a class or
  interface, it marks the members that type declares and their overrides;
  members a subclass adds are still reported. It suits objects read by
  serialisation, for example response objects that graphql-php reads property
  by property, or DTOs that Symfony's serializer constructs.
- **`@phpstan-ignore`** with the finding's identifier and a reason handles a
  single finding. The identifiers are `shipmonk.deadMethod`,
  `shipmonk.deadConstant`, `shipmonk.deadEnumCase`,
  `shipmonk.deadProperty.neverRead` and `shipmonk.deadProperty.neverWritten`.
- **A baseline** handles legacy code.

The package ships no attribute of its own for marking code as used, because
production code would then depend on a dev-only package. If core adopts one,
the package will recognise it.

For a caller this package does not know, such as a contrib module or a
library, write a usage provider of your own: a few lines of PHP, registered
in your PHPStan config. For example, `myclabs/php-enum` reads its enums'
constants by name, so a site using it adds this provider:

```php
<?php

namespace MySite\PHPStan;

use ReflectionClassConstant;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

final class MyCLabsEnumUsageProvider extends ReflectionBasedMemberUsageProvider
{
    protected function shouldMarkConstantAsUsed(ReflectionClassConstant $constant): ?VirtualUsageData
    {
        return $constant->getDeclaringClass()->isSubclassOf('MyCLabs\Enum\Enum')
            ? VirtualUsageData::withNote('Enum value, read by MyCLabs\Enum\Enum')
            : null;
    }
}
```

PHPStan builds its services before it loads `bootstrapFiles`, so make the
class autoloadable through Composer's `autoload-dev`. Then register it:

```neon
services:
    -
        class: MySite\PHPStan\MyCLabsEnumUsageProvider
        tags:
            - shipmonk.deadCode.memberUsageProvider
```

[Writing a usage provider](docs/extending.md) covers the other shapes a
provider takes, including the bases this package offers for members called by
name, how to check that a provider works, and when the mechanism belongs in
this package instead.

## Documentation

- [Coverage](docs/coverage.md): Drupal's documented mechanisms mapped to the
  providers, and what is not covered.
- [Twig templates](docs/twig.md): how name matching works, what it misses,
  and the measurement behind keeping it.
- [Writing a usage provider](docs/extending.md): for custom code, contrib
  modules and libraries.
- [Related work](docs/related-work.md): shipmonk, phpstan-drupal, Drupal
  Canvas and drupal-rector, and what this package takes from each.
- Measurements on [dpl-web] and [Canvas][canvas].

## Development

See [AGENTS.md](AGENTS.md) for the architecture, conventions and commands.

## License

MIT, see [LICENSE](LICENSE).

[canvas]: docs/measurements/canvas.md
[dcd]: https://github.com/shipmonk-rnd/dead-code-detector
[dpl-web]: docs/measurements/dpl-web.md
[extension-installer]: https://github.com/phpstan/extension-installer
[phpstan-drupal]: https://github.com/mglaman/phpstan-drupal
