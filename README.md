# Dead code detector for Drupal

[shipmonk/dead-code-detector][dcd] extension that teaches it how Drupal calls
code, so it reports dead class members in your custom modules, themes and
profiles rather than Drupal's wiring.

## Summary

- ✅ **PHPStan** extension, on top of shipmonk/dead-code-detector
- 🧩 **Drupal's wiring** understood: services, routes, hooks, plugins,
  entities, callbacks, Twig templates, Drush
- 📦 **Core and contrib** treated as library code, wherever Composer puts them
- 🧪 **Dead tested code** detection for Drupal's `tests` directories
- 🔌 **Customizable** with usage providers of your own
- ⚙️ **No configuration** beyond two switches

## Installation

```sh
composer require --dev reload/dead-code-detector-drupal
```

Use [phpstan/extension-installer][extension-installer] or load the extensions
yourself:

```neon
# phpstan.neon.dist
includes:
    - vendor/mglaman/phpstan-drupal/extension.neon
    - vendor/shipmonk/dead-code-detector/rules.neon
    - vendor/reload/dead-code-detector-drupal/extension.neon
```

- Drupal 11.3 and later, PHP 8.3 and later.
- Drupal 10 is not supported. Sites on `drupal/core-dev` cannot install it,
  since core-dev pins PHPStan 1 there; others can, untested.
- [phpstan-drupal][phpstan-drupal] comes with it and is required: without it,
  PHPStan analyses no `.module`, `.theme`, `.install` or `.inc` files, so code
  only procedural hooks call looks dead, and it cannot load core modules' and
  contrib's classes, so overrides of their members look dead too.

> [!NOTE]
> Analyse all your custom code: every custom module, theme and profile, tests
> included. Code outside the analysed paths does not count as library code,
> its callers are invisible, and overrides of its members can be reported.

**Map your custom entity types for phpstan-drupal.** The detector only follows
calls whose target PHPStan can type, and phpstan-drupal only knows core's entity
types. For yours, `$entity->foo()` otherwise keeps every method named `foo`
alive, or with dead-code-detector's `usageOverMixed` excluder reports false
positives instead.

```neon
parameters:
    drupal:
        entityMapping:
            my_entity:
                class: Drupal\my_module\Entity\MyEntity
```

## Usage

```sh
vendor/bin/phpstan
```

A module whose `Importer` service is wired in `example_module.services.yml`,
with a constant nobody reads, a method nobody calls and a method only a unit
test calls, reports:

```text
 ------ -----------------------------------------------------------------------
  Line   web/modules/custom/example_module/src/Importer.php
 ------ -----------------------------------------------------------------------
  11     Unused Drupal\example_module\Importer::BATCH_SIZE
         🪪  shipmonk.deadConstant
  14     Property Drupal\example_module\Importer::$logger is never read
         🪪  shipmonk.deadProperty.neverRead
  17     Unused Drupal\example_module\Importer::import
         🪪  shipmonk.deadMethod
         💡  Thus Drupal\example_module\Importer::normalise is transitively
         unused
  27     Unused Drupal\example_module\Importer::fromLegacyExport (all usages
         excluded by drupalTests excluder)
         🪪  shipmonk.deadMethod
 ------ -----------------------------------------------------------------------
```

- The constructor is not listed: the services file wires the class, and the
  container's construction counts.
- `$logger` is injected and never used, the commonest finding on real sites.
- `normalise()` is reached only through the dead `import()`, so it goes with
  it instead of getting a line of its own.
- `fromLegacyExport()` has callers, but only under `tests`, so it can be
  deleted together with its test.
- The identifier after 🪪 is what `@phpstan-ignore` takes.

## Supported Drupal mechanisms

### Services

- `*.services.yml`: constructors, autowired services included; `factory`,
  `calls` and `configurator` methods; the methods core calls through the
  `access_check`, `cache.context` and `service_collector` tags
- `getSubscribedEvents()` and `#[AsCommand]` through dead-code-detector's
  Symfony support

### Routing

- `*.routing.yml`: `_controller` as `Class::method`, `service:method` or an
  invokable class, `_title_callback`, `_custom_access`, `_form` and
  `route_callbacks`, with the constructors of the classes Drupal builds
- `#[Route]` on controllers (Drupal 11.4+): the constructor; dead-code-detector
  counts the routed methods
- `*.permissions.yml` `permission_callbacks`
- access checkers' `access()`

### Hooks

- `#[Hook]` on a method, or on a class with `method:` or `__invoke`, in
  modules and themes, with the constructors of hook classes
- Hux's `#[Hook]`, `#[Alter]` and `#[ReplaceOriginalHook]`
- Procedural hooks need nothing: dead-code-detector does not report functions,
  and every function body is an entry point

### Plugins, entities and validation

- plugin attribute properties, which `AttributeBase::get()` reads
- config entity properties, written on load and read on save
- `#[ActionMethod]` methods of config entities, which recipes call
- constraint properties set from an options array
- `*.schema.yml` `Callback` constraints

### Callbacks

- `'::method'`, `'Class::method'`, `'Class:method'` and `'service:method'`,
  written out or built with `Foo::class . '::method'`, in form, render, Ajax
  and batch callbacks, `hook_theme()` and field definitions
- the constructor too, when Drupal instantiates the class for the call
- they count from where they appear, so a callback named only by dead code
  is dead too; array callables are dead-code-detector's own

### Twig templates

- names that templates under the analysed paths access, limited to the
  methods Drupal's Twig sandbox lets templates call, with the sandbox lists
  read from the site's settings files
- see [Twig templates](docs/twig.md) for what this covers and misses

### Calls by name

- REST resources' HTTP methods: `get()`, `post()`, `patch()` and the rest
- JSON:API resources' `process()`
- GraphQL data producers' `resolve()`
- exception subscribers' `on404()` and `on4xx()` handlers

### Drush

- commands with `#[CLI\Command]` or `@command`
- hooks with `#[CLI\Hook]` or `@hook`
- command class constructors, and `create()` factories
- Drush 13's `#[AsCommand]` commands and `#[AsEventListener]` listeners,
  through dead-code-detector's Symfony support

### dead-code-detector's own providers, active in any Drupal site

- Symfony event subscribers, `#[Required]`, `#[Route]` methods,
  `#[AsMessageHandler]` handlers
- Twig extension callbacks, PHPUnit tests and data providers, Behat contexts

The [coverage map](docs/coverage.md) lists Drupal's documented mechanisms one
by one, with what covers each and what is not covered.

## Library code

- Drupal core, contrib modules and themes, and everything else Composer installs
  is library code, wherever Composer put it: `web/core`, `web/modules/contrib`
  or `vendor/`. dead-code-detector alone treats only `vendor/` that way.
- Library code is never reported, and a member that overrides or implements
  a library member counts as used: a Views field plugin's `render()`, a
  migrate process plugin's `transform()`.

## Excluding usages in tests

- Usages from a `tests` directory below an analysed path do not keep code
  outside tests alive, whether PHP calls, a test module's YAML or a test
  theme's templates make them.
- Test code itself is still analysed.
- Members only tests use are reported with a suffix, as `fromLegacyExport()`
  above: `(all usages excluded by drupalTests excluder)`.
- dead-code-detector's own tests excluder works from `autoload-dev`, which
  Drupal sites do not declare; this one replaces it.

## Configuration

These are the defaults; your own config overrides them:

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

Those two switches are the whole configuration, by design:

- Everything else is read from the site: services, routes, schema and
  permissions from the YAML under the analysed paths, and the Twig sandbox's
  `twig_sandbox_allowed_*` lists from the PHP files in `sites/*/`, read
  statically, with core's defaults as the fallback. Nothing is declared
  twice, so nothing drifts.
- Contrib modules are covered one at a time, each with a provider that knows
  how that module calls code. A list of attribute or class names could not
  say that, so there is none to configure.
- Site-specific callers get a provider in the site, or an `@api` tag; see
  [customization](#customization).

dead-code-detector's own parameters still apply. This package cannot change
their defaults, because extension-installer loads dead-code-detector's rules
after this package's, so switching off a kind of finding is yours to do.
`shipmonkDeadCode.usageExcluders.usageOverMixed.enabled` is the one most sites
want to decide on.

## Customization

Use dead-code-detector's built-in mechanisms when the detector cannot see a
caller:

- `@api` on a member marks it and its overrides as used; on a class or
  interface, the members that type declares and their overrides. It suits
  objects read by serialisation: graphql-php response objects, DTOs that
  Symfony's serializer constructs.
- `@phpstan-ignore` with the identifier and a reason handles one finding.
- A baseline handles legacy code.

The package ships no attribute of its own for marking code as used, because
production code would then depend on a dev-only package.

For a caller this package does not know, such as a contrib module or a
library, write a usage provider: a few lines of PHP, registered in your
PHPStan config. `myclabs/php-enum` reads its enums' constants by name, so a
site using it adds:

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

```neon
services:
    -
        class: MySite\PHPStan\MyCLabsEnumUsageProvider
        tags:
            - shipmonk.deadCode.memberUsageProvider
```

PHPStan builds its services before it loads `bootstrapFiles`, so the class
must be autoloadable, for example through Composer's `autoload-dev`.
[Writing a usage provider](docs/extending.md) covers the other shapes a
provider takes, the bases this package offers for members called by name, and
when the mechanism belongs in this package instead.

## Limitations

### By design

- Wiring is read statically: Drupal is not bootstrapped and no database is
  needed ([why](docs/design.md#principles)). Wiring done in PHP, such as a
  service provider's `register()`, is seen only where it names a method as a
  string callable.
- Templates are matched by name, so a coincidental name keeps a dead member
  alive, and a sandbox list computed at runtime is not seen. That costs about
  one hidden finding per site; see [Twig templates](docs/twig.md).
- When unsure, code is used: a call on a value PHPStan cannot type keeps
  every member of that name alive, because a report is something people act
  on by deleting code.
- Only custom code is reported. There is no mode for contrib modules, where
  every public member is potential API, and no reporting of dead functions,
  which dead-code-detector does not analyse.
- Tests are a directory named `tests` below an analysed path; a test module
  elsewhere counts as production code.

### Not covered yet

- Constructors reached only through core's `AutowireTrait::create()`, such as
  forms built with `getForm()` or validators with injected services, when
  nothing this package reads names the class.
- The rest is in the [coverage map](docs/coverage.md#not-covered).

### Callers outside Drupal's wiring

- Symfony's serializer constructing input objects, graphql-php reading response
  properties by name, and a `method_exists()` check before a call are invisible
  to dead-code-detector and to this package alike. Mark such classes with
  `@api`, or write a provider for the library.

### In dead-code-detector

- A property that a trait, the class using it or that class's parent declares
  again is reported as unused.
- A subclass redeclaring a property with a default does not count as writing
  it.

## Debugging

To see why a member counts as used, or does not, use dead-code-detector's
[debugging][dcd-debugging]: list the member under
`shipmonkDeadCode.debug.usagesOf` and run PHPStan with `-vvv`. The note of
the provider that marked it is printed with each usage.

Changing a provider's code does not invalidate PHPStan's result cache, so run
`phpstan clear-result-cache` after editing one.

## Documentation

- [Coverage](docs/coverage.md): Drupal's documented mechanisms mapped to the
  providers, and what is not covered.
- [Design](docs/design.md): why the code is structured, checked and tested as
  it is, and what a new provider needs.
- [Twig templates](docs/twig.md): how name matching works, what it misses,
  and the measurement behind keeping it.
- [Writing a usage provider](docs/extending.md): for custom code, contrib
  modules and libraries.
- [Related work](docs/related-work.md): dead-code-detector, phpstan-drupal,
  Drupal Canvas and drupal-rector, and what this package takes from each.
- Measurements on [dpl-web] and [Canvas][canvas].

## Contributing

- `make checks` runs everything CI runs; `make help` lists the targets.
- Commit messages follow [Conventional Commits][conventional-commits], which
  `make lint-commits` checks.
- Start with the [design](docs/design.md) before changing the code.

## Releases

On each push to `main`, semantic-release reads the new commits: a `fix`
commit makes a patch release, a `feat` commit a minor release, a `!` after the
type or a `BREAKING CHANGE` footer a major release, and other types none. A
release creates a Git tag and a GitHub release and updates `CHANGELOG.md`;
Packagist picks the tag up through its GitHub hook. Versions start below 1.0
from the `v0.0.0` tag on the first commit. The release commit is pushed with
the `GH_RELEASE_TOKEN` secret, a token with `Contents: Read and write` from an
account on the bypass list of the ruleset on `main`.

## License

MIT, see [LICENSE](LICENSE).

[canvas]: docs/measurements/canvas.md
[conventional-commits]: https://www.conventionalcommits.org/
[dcd]: https://github.com/shipmonk-rnd/dead-code-detector
[dcd-debugging]: https://github.com/shipmonk-rnd/dead-code-detector#debugging
[dpl-web]: docs/measurements/dpl-web.md
[extension-installer]: https://github.com/phpstan/extension-installer
[phpstan-drupal]: https://github.com/mglaman/phpstan-drupal
