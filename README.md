# Dead code detector for Drupal

This package adds Drupal support to [shipmonk/dead-code-detector][dcd]: usage
providers that teach it how Drupal calls code. Without them, the detector
reports Drupal's own wiring as dead code. With them, it reports the dead class
members in custom code for Drupal 11.3 and later.

**Status**: the spike is complete and the package is not released. On
dpl-web, 78% of findings are real dead code. On Drupal Canvas, it finds every
dead member that Canvas's maintainers removed. On ten Reload client projects,
22 of 23 findings are members nothing uses. See the measurements for
[dpl-web](docs/measurements/dpl-web.md), [Canvas](docs/measurements/canvas.md)
and [Reload's projects](docs/measurements/reload-projects.md), and the
[design](docs/design.md).

## What it does

shipmonk's detector reports class members that nothing uses: methods,
constants, enum cases, and properties that are never read or never written.
This package tells it which code is yours, and how Drupal calls that code.

### Code loading

- **Only custom code is reported.** Drupal core, contrib modules and themes,
  and everything else Composer installs is library code, wherever Composer
  put it: `web/core`, `web/modules/contrib` or `vendor/`. shipmonk alone treats
  only `vendor/` that way. Library code is never reported, and a member that
  overrides or implements a library member counts as used, such as a Views
  field plugin's `render()` or a migrate process plugin's `transform()`.
- **The cache follows Drupal's wiring.** PHPStan's result cache only tracks
  PHP files. This package adds the files it reads to the cache key: Drupal's
  YAML wiring under the analysed paths, Twig templates and the site's settings
  files. Changing or moving one of them makes PHPStan analyse everything
  again.
- **Tests do not keep code alive.** Usages in a `tests` directory do not keep
  code outside tests alive, whether PHP calls, a test module's YAML or a test
  theme's templates make them. The report says when that is the only reason a
  member is dead. Test code itself is still analysed. As in shipmonk's own
  tests excluder, a call on a value of unknown type still keeps every method
  with that name alive.

### Member usage detection

A member counts as used when Drupal calls it through any of the following:

- **Hooks**:
  - Procedural hooks: shipmonk does not report functions, and every function
    body is an entry point, hook or not. Whatever a function calls counts as
    used.
  - OOP hooks: `#[Hook]` on a method, or on a class with `method:` or
    `__invoke`, in modules and themes. Hux's `#[Hook]`, `#[Alter]` and
    `#[ReplaceOriginalHook]` count too, and so do the constructors of hook
    classes.
- **Services** (`*.services.yml`):
  - constructors, also of autowired services
  - factories, `calls` and configurators
  - methods called through the `access_check` and `cache.context` tags
- **Routes**:
  - `*.routing.yml`: controllers, whether `Class::method`, `service:method` or
    invokable, title callbacks, `_custom_access`, forms and `route_callbacks`,
    and the constructors of the classes Drupal builds for them.
  - `#[Route]` (Drupal 11.4+): the constructors of the controllers core finds.
    shipmonk itself counts the routed methods.
- **Plugins, entities and validation**:
  - plugin attribute properties, which `AttributeBase::get()` reads
  - config entity properties, which Drupal writes when it loads the entity
    and reads when it saves it
  - `#[ActionMethod]` methods of config entities, which recipes can call
  - constraint properties set from an options array, for constraints whose
    constructor does not take named arguments
  - config schema `Callback` constraints
- **Drush**: commands and hooks, by attribute or annotation, and command class
  constructors.
- **String callables**: `'::method'`, `'Class::method'`, `'Class:method'` and
  `'service:method'`, written out or built with `Foo::class . '::method'`,
  and the constructor when Drupal instantiates the class for the call. They
  count as used from where they appear, so a callback named only by dead code
  is dead too.
- **Twig templates**: names that templates under the analysed paths access,
  limited to the methods Drupal's Twig sandbox lets templates call. The
  sandbox's lists are read from the site's settings files. See [Twig
  templates](docs/twig.md) for what this covers and misses.
- **Calls by name**: members that core and contrib use by name on subtypes of
  a type that does not declare them:
  - REST resources' HTTP methods
  - JSON:API resources' `process()` and GraphQL data producers' `resolve()`
  - access checkers' `access()`
- **shipmonk's built-in support**, which switches on in any Drupal site:
  - Symfony event subscribers, `#[Required]` setters and properties,
    `#[Route]` methods and `#[AsMessageHandler]` handlers
  - Twig extension callbacks
  - PHPUnit tests and data providers, when PHPUnit is installed
  - Behat contexts, when Behat is installed

[Coverage](docs/coverage.md) lists the documented Drupal mechanisms one by one,
with what covers each.

## Installation

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
from [phpstan-drupal][phpstan-drupal]. phpstan-drupal only knows core's entity
types. For your own, calls on loaded entities have no known type, and the
detector then has to guess:

- **By default**, a call like `$entity->foo()` keeps every method named `foo`
  alive, which hides dead code.
- **With `usageOverMixed`**, it reports false positives instead.

Map your entity types:

```neon
parameters:
    drupal:
        entityMapping:
            my_entity:
                class: Drupal\my_module\Entity\MyEntity
```

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

The Twig sandbox needs no configuration. The package reads the
`twig_sandbox_allowed_classes`, `twig_sandbox_allowed_methods` and
`twig_sandbox_allowed_prefixes` settings from the PHP files in your Drupal
root's `sites/*/` directories, statically, and falls back to core's defaults.
It reads literal lists only, so a list computed at runtime, for example from
an environment variable, is not seen.

shipmonk's own settings still apply. For example,
`shipmonkDeadCode.usageExcluders.usageOverMixed.enabled: true` stops calls on
untyped values from keeping every method with that name alive.

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

For a caller this package does not know, such as a contrib module or a
library, write a usage provider of your own. See shipmonk's
[customization][dcd-customization] docs. For example, `myclabs/php-enum`
reads its enums' constants by name. A site that uses it can add this
provider:

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

## Development

See [AGENTS.md](AGENTS.md) for the commands and conventions, and
[related work](docs/related-work.md) for how this package relates to existing
tools.

## License

MIT, see [LICENSE](LICENSE).

[dcd]: https://github.com/shipmonk-rnd/dead-code-detector
[dcd-customization]: https://github.com/shipmonk-rnd/dead-code-detector#customization
[extension-installer]: https://github.com/phpstan/extension-installer
[phpstan-drupal]: https://github.com/mglaman/phpstan-drupal
