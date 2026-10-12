# Design

What this package decided and why. The decisions were made during a spike in
October 2026 and hold for the current code; where one changed, the text says
why. The [README](../README.md) describes the result for users, and
[measurements](#evidence) hold the numbers.

This package teaches [shipmonk/dead-code-detector][dcd] (DCD) how Drupal calls
code. DCD can then report dead class members in Drupal sites without burying
the real findings in false positives.

## Evidence

Measured on two corpora, each with its own report:

- **[dpl-web](measurements/dpl-web.md)**: 314 findings in hand-written code
  become 60, of which 47 are real dead code (78%). Every kind of member
  passes the 80% bar except never-written properties, where a DCD limitation
  with shadowed trait properties causes six of eight findings.
- **[Drupal Canvas](measurements/canvas.md)**: every dead member that
  Canvas's maintainers removed is still reported, and the package explains all
  but 3 of the in-scope findings Canvas had kept alive with 12 hand-written
  providers.

DCD without Drupal knowledge is unusable on Drupal code: Canvas measured
about 6% precision on its zero-config run.

## Scope

- **Custom code in Drupal site projects**: whatever PHPStan analyses. Core,
  contrib and everything else Composer installs is [library
  code](#library-code). It is never reported, and members that override or
  implement it count as used. There is no mode for contrib modules, where every
  public member is potential API.
- **Drupal 11.3+ and 12.** Drupal 10 is out of scope. DCD 1.x requires
  `phpstan/phpstan ^2.1.41`. `drupal/core-dev` requires PHPStan 1 up to Drupal
  11.1, and that includes all of Drupal 10. Drupal 10 reaches end of life on
  2026-12-16.
- **Annotations, attributes and OOP hooks.** Plugin annotations, plugin
  attributes, `#[Hook]` classes, `#[Route]` controllers and `#[ActionMethod]`
  methods are all recognised. See [attributes](#attributes).
- **Class members only.** DCD does not analyse functions, and function bodies
  are entrypoints. So anything a procedural hook calls stays alive, and dead
  procedural functions are not reported. Migrating procedural hooks to OOP hooks
  is the job of drupal-rector's `HookConvertRector`. See [related
  work](related-work.md).

## Determining usage

### Library code

A member is used if it, or a member it overrides or implements, is declared
in a file under the install path of any Composer package except the root
package. So library code in the analysed paths is never reported, the members
of a library trait no analysed class uses included. The install paths come
from `Composer\InstalledVersions`. Both sides of the
comparison are normalised lexically, and the real paths of symlinked path
repositories are added.

This is DCD's own vendor rule, generalised. DCD's `VendorUsageProvider` only
checks the vendor directories of the registered class loaders.
`composer/installers` places core and contrib outside `vendor/`:

- Core's `lib/` classes only match by accident. Composer hands out
  un-normalised paths like `vendor/composer/../../web/core/lib/…`.
- Core modules, `core/tests` and contrib do not match at all.

Consequence: analyse all custom code. Custom code left out of the analysed
paths does not count as library code.

### Wiring is read statically

Providers read YAML, annotations, attributes and templates under the analysed
paths. Drupal is not bootstrapped and no database is needed. This matches
DCD's Nette provider (config files) and Laravel provider (conventions). DCD's
Symfony provider reads a container XML that Symfony writes on its own. Drupal
produces no such file.

Revisit if measurement finds false positives that only the runtime knows
about. The answer would then be a Drush command that dumps the container,
routes and plugin definitions for a provider to read (compare issue #838 in
phpstan-drupal). Neither benchmark found such false positives. The one
service registered in PHP, Canvas's access checker, is covered through
`AccessInterface`.

### When unsure, code is used

The default is conservative. A dead-code report is a claim that people act on
by deleting code.

There are switches to tighten it:

- DCD's `usageOverMixed` excluder
- `drupalDeadCode.twigTemplates.enabled`, and the
  [sandbox settings](#twig-name-matching)

### Tests

Tests are analysed. This package's `drupalTests` excluder makes usages from a
`tests` directory below an analysed path stop keeping code outside tests
alive. Code that only tests use is reported as such, so it can be deleted
together with its tests.

Usages that wiring or templates declare come from those files, so a test
module's routing or a test theme's template counts as test code. DCD's
virtual usages carry no file, and its `UsageOrigin` constructor is internal,
so the providers record each usage's files beside it for the excluder.

DCD's own tests excluder works from `autoload-dev`, which Drupal sites do not
declare. Only directories below an analysed path count, so a checkout under a
directory named `tests` is not all test code.

### Member kinds

Every DCD kind is reported: methods, constants, enum cases, and properties
that are never read or never written. The plan was to ship any kind below 80%
precision switched off. That turned out to be impossible, because
extension-installer loads DCD's `rules.neon` after this package's
`extension.neon`, so DCD's defaults win.

The weak kinds were fixed instead:

- **Never-written properties**: dynamic writes are covered by the providers
  for config entities and constraints.
- **Constants**: a library that reads constants by name, such as
  `myclabs/php-enum` on dpl-web, is covered by a provider in the site's own
  config.

On dpl-web, every kind passes except never-written properties: six of their
eight findings are the [trait limitation](#known-limitations).

### Marking code as used

Use DCD's built-ins, documented with Drupal recipes in the
[README](../README.md#when-the-detector-cannot-see-the-caller):

- `@api` phpdoc on members, classes or interfaces. It is inherited by
  descendants.
- `@phpstan-ignore shipmonk.…` with a reason.
- The baseline.

The package ships no attribute of its own, because production code would then
depend on a dev-only package. If core adds one, recognise it. Canvas work item
[#3591672][is-called-by-drupal] proposes `#[IsCalledByDrupal(reason)]` for
Canvas itself.

[Coverage](coverage.md) maps Drupal's documentation to these providers, mechanism
by mechanism, and says what is not covered.

| Provider | Marks as used |
|---|---|
| Library code | Members of Composer-installed code, and members overriding or implementing them |
| `*.services.yml` | Constructors of services, including autowired ones and services whose id is a class name. `factory` (including invokable `@service`), `configurator` and `calls` methods. Methods of `access_check` (its `method`, `access` by default) and `cache.context` tags, which core calls whether or not an interface promises them. Abstract services are skipped; `parent` is resolved. |
| `*.routing.yml` | `_controller` (`Class::method`, `service:method`, or an invokable class's `__invoke`), `_title_callback`, `_custom_access`, `route_callbacks`, and constructors of the classes the class resolver instantiates, forms included |
| `#[Route]` (Drupal 11.4+) | Constructors of the controllers core's `AttributeRouteDiscovery` finds: non-abstract classes in a module's `Controller` namespace with `#[Route]` on a method, inherited ones included, or on an invokable class. DCD's Symfony support marks the routed methods. |
| `*.schema.yml` | `Callback` constraint callbacks |
| `#[Hook]` | Method-level, class-level with `method:` (named or positional) and class-level on `__invoke`, in themes too. Hux's method attributes `#[Hook]`, `#[Alter]` and `#[ReplaceOriginalHook]`. Constructors of hook classes. |
| `#[ActionMethod]` | Public methods with the attribute on config entities, as core's `EntityMethodDeriver` finds them: inherited ones too, and overrides that lack the attribute. Recipes call them through config actions. |
| Drush | Methods with `#[CLI\Command]`, `#[CLI\Hook]`, `@command` or `@hook`. Constructors of `DrushCommands` subclasses. |
| String callables | `'::method'` on the form object, `'Class::method'`, `'Class:method'`, `'service:method'` for services declared in the analysed code, and `Foo::class . '::method'` or `static::class . ':method'`, when the service wiring or reflection knows the target. The constructor too, when the class resolver instantiates the class for a non-static method. They count as used from where the string is, so they are transitively dead when that code is. |
| Calls by name | Methods and properties that core and contrib use by name on subtypes of a type, one provider per mechanism; see [calls by name](#calls-by-name). Trait properties are referenced through the class using the trait, which shipmonk resolves to the trait. |
| Twig name matching | See [below](#twig-name-matching) |

The following DCD built-ins switch on automatically in any Drupal site and need
no provider:

- **Symfony** (any `symfony/*` package installed):
  `EventSubscriberInterface::getSubscribedEvents()`, `#[Required]`, methods
  with `#[Route]`, and `#[AsMessageHandler]` handlers
- **Twig**: `TwigFilter`, `TwigFunction` and `TwigTest` callbacks
- **PHPUnit** (when `phpunit/phpunit` is installed): test methods and their
  data providers

**The plugin-discovery provider was not built.** On dpl-web no remaining
finding was a plugin constructor: plugins have `create()`, which the
library-code rule covers. On Canvas, plugin attribute properties were what
remained, and a provider of their own covers them.

### Calls by name

Core and contrib call some members by name on subtypes of a type that does
not declare them. Each mechanism has a provider of its own, as each framework
does in DCD, so it can be as precise as the mechanism allows. Each lives in a
directory named after its Drupal area, such as `src/Core/Rest` or
`src/Core/Validation`, with the shared bases in `src/Support/Reflection`. They
come from concrete findings:

| Type | Members | Found in |
|---|---|---|
| `Drupal\rest\Plugin\ResourceInterface` | HTTP verb methods | dpl-web: 6 |
| `Drupal\graphql\Plugin\DataProducerPluginInterface` | `resolve` | dpl-web: 23 |
| `Drupal\jsonapi_resources\Resource\ResourceBase` | `process` | other sites: 1 |
| `Drupal\Core\Routing\Access\AccessInterface` | `access` | Canvas: 1, registered in PHP |
| `Drupal\Core\Config\Entity\ConfigEntityBase` | properties, read and written | Canvas: ~38 |
| `Drupal\Component\Plugin\Attribute\AttributeBase` | properties, read | Canvas: ~8 |
| `Symfony\Component\Validator\Constraint` | properties, written, unless the constructor has `#[HasNamedArguments]` | Canvas: ~16 |

Types are matched by name, including the traits of traits and of parents, so
a type from a module that is not installed costs nothing. Private properties
do not count: the base class accesses properties from its own scope, which
cannot see a subtype's private ones.

Only Drupal core and contrib get providers. Another library's calls by name,
such as `MyCLabs\Enum\Enum` reading its subclasses' constants, belong in a
provider in the site, or in a DCD extension for that library. Like DCD, the
package has no setting for them.

### Attributes

An attribute keeps code alive only when Drupal calls the code because of it.
Core's `#[TrustedCallback]` and `#[TwigAllowed]` only allow a call,
`#[JsonSchema]` describes a return value, and `#[ReorderHook]` and
`#[RemoveHook]` act on other implementations, so none of them count.

**Contrib attributes are supported one module at a time.** On 2026-10-04,
299 distinct contrib modules were scanned: those installed in the benchmark
sites, and 191 widely used or attribute-heavy modules from drupal.org. Only
two have attributes that make Drupal call the method they are on:

- **Hux** offered OOP hooks before core did, and sites that adopted it have
  not all migrated, so it is supported.
- **Pinto** builds theme definitions from `#[ThemeDefinition]` methods. No
  benchmark project uses it, so it is not.

So there is no setting for site-specific attributes. For a module this
package does not know, a site can use `@api`, `ignoreErrors` or its own usage
provider.

### Twig name matching

Templates do not say what class a variable holds, so a public member of any
analysed class counts as used if a template accesses its name and Drupal's
Twig sandbox lets templates call it. The sandbox lists are read from the
site's settings. Typing template variables, as TwigStan does, was measured
and rejected. [Twig templates](twig.md) records what this covers and misses,
the measurement, the decision and how it came about.

## Cross-cutting

- **Result cache.** A result-cache meta extension hashes every wiring and
  settings file the providers read, and each wiring file's path within the
  analysed tree, which the tests excluder depends on. Paths within the tree
  let the cache survive the checkout moving. Canvas's YAML-based providers
  served stale results from a warm cache.
  - PHPStan's result cache does not notice code changes in this package when
    it is installed from a Composer `path` repository. That only matters when
    developing the package, because installing a new version changes
    `composer.lock`.
- **phpstan-drupal is required.** Without it, PHPStan analyses no
  procedural files and cannot load core modules' and contrib's classes, so
  what they call or override looks dead. Its typing of `\Drupal::service()`
  and `$container->get()` also feeds DCD's call graph.
- **Discovery.** Wiring files are found under PHPStan's analysed paths,
  skipping `node_modules`, `vendor` and `.git`.
  - phpstan-drupal's maps do not serve here. Its `ServiceMap` drops calls,
    tags, factories, configurators and the file a service came from, and its
    `ExtensionMap` ignores the analysed and excluded paths. Both are static
    state, filled at bootstrap and due to change (phpstan-drupal #1051). Not
    building on them also keeps an upstream move possible.
- **Readers and decisions are separate.** A reader is a service the container
  builds from its sources and injects into providers; it reads once, on first
  use. A decision is a static function of data, `parse()` included, so tests
  exercise it without the filesystem, and `@phpstan-pure` lets PHPStan check
  it. Nothing static reads the world. phpat rules in `tests/Architecture` keep
  `Support` free of Drupal areas and readers in the container, and shipmonk's
  rules fix the class suffixes.
- **Configuration.** Two switches under `drupalDeadCode`, for Twig matching
  and the tests excluder, and nothing else. Wiring and the Twig sandbox lists
  are read from the site, so nothing is declared twice and nothing drifts;
  contrib is covered one module at a time with a provider each, because a
  list of attribute or class names cannot say how a mechanism calls code; and
  site-specific callers get a provider in the site or an `@api` tag. The two
  parameters are a published contract; see the
  [README](../README.md#configuration).
- **Rule tests.** DCD's rule keeps state between analyses, so each analysis
  runs once: every test class with the same config files shares one container
  and one analysis of all their fixtures, and keeps the messages about its own,
  which live in `Drupal\example\<Mechanism>`. A class then checks one member
  per test. The container boots phpstan-drupal on the Drupal core
  installed for development, so fixtures build on core's real classes and are
  typed as on a site. Only contrib and Drush classes are stubbed.

## Measurement corpora

- **dpl-web** (`cms/`): Drupal 11.4, PHPStan 2.2. 67 custom modules, the
  `dpl_cms` profile and the `novel` theme. A dpl-web developer reviewed every
  finding; see the [maintainer review](measurements/dpl-web.md#maintainer-review).
- **Drupal Canvas**, at the commit before its DCD adoption, as a custom
  module. Its maintainers' triage serves as labels.

The bar was set before measuring: at least 80% of the remaining findings are
real dead code, measured per member kind; every false positive is attributed
to a named mechanism, statically coverable or runtime-only; and a meaningful
amount of real dead code is found. dpl-web meets all three, and every false
positive there is outside Drupal's wiring.

## Known limitations

These DCD limitations were found by the benchmarks and are not fixable in this
package:

- **Shadowed trait properties**: when a trait composing a trait, the class
  using it or that class's parent declares the trait's property again, PHP
  merges the declarations into one property. DCD keeps them apart and
  attributes reads and writes to one of them, so the other is reported. On
  dpl-web this pointed to needlessly redeclared properties (7).
- **Redeclared properties**: a subclass redeclaring a property with a default
  is not counted as writing the parent's property (Canvas: 2).

### Callers outside Drupal's wiring

On dpl-web, graphql-php's default field resolver reads response objects'
properties by name (3), and Symfony's serializer constructs input objects
(3). Elsewhere, a contrib field formatter calls `label()` on plugins after
checking `method_exists()` (1). Neither DCD nor this package sees these
callers. The options, from no code to new providers:

1. **`ignoreErrors` in the site's PHPStan config**, by identifier and path.
   No code changes, but dead code that later appears in those paths is hidden
   too.
2. **`@api` on the classes.** Precise, and documents the intent. It changes
   only docblocks in the site.
3. **A usage provider in the site**, a few lines of PHP registered in its
   PHPStan config, as DCD documents.
4. **A GraphQL provider in this package.** Public properties of objects that
   a data producer's `resolve()` returns are read by graphql-php's default
   resolver. Recurse into nested objects, as DCD's Twig provider does for view
   data. `drupal/graphql` is Drupal contrib, so this is in scope, like the
   data producer provider.
5. **A serializer provider.** Constructors of classes passed to
   `deserialize()` or `denormalize()`, including the `Foo::class . "[]"`
   array form, count as used. Its natural home is DCD's Symfony support, which
   today only handles `#[MapRequestPayload]`, so it is an upstream candidate.
   This package could carry it meanwhile, since Drupal ships the serializer.

## Open threads

- **Upstream candidates for DCD**: the generic install-path fix to
  `VendorUsageProvider`, `MyCLabs\Enum\Enum` constants, Symfony serializer
  denormalisation, the two DCD limitations above, and built-in Drupal
  support, which DCD's README allows for frameworks.
- **Upstream to phpstan-drupal**: the providers could ship as an optional
  part of it, active when DCD is installed, since most of the Drupal knowledge
  they need lives there already. A runtime metadata dump, proposed in
  phpstan-drupal's issue #838 and deferred here, could serve both.
- **Canvas**: the benchmark suggests this package could replace most of its
  providers; its maintainers have not been contacted.
- **Form routes from `#[Route]`**, once core releases them
  ([#3584793][form-routes]). Core will find forms in a module's `Form`
  namespace by a class-level `#[Route]`, and their constructors need the
  treatment controllers get.
- **Not planned**: Drupal 10, a contrib mode, dead functions, bootstrapping
  Drupal.

[dcd]: https://github.com/shipmonk-rnd/dead-code-detector
[form-routes]: https://www.drupal.org/project/drupal/issues/3584793
[is-called-by-drupal]: https://git.drupalcode.org/project/canvas/-/work_items/3591672
