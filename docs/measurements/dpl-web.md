# Measurements: dpl-web

These are runs of this package against [dpl-web][dpl-web]'s Drupal site
(`cms/`). The bar, set before measuring: at least 80% of the remaining
findings are real dead code, per member kind; every false positive is
attributed to a named mechanism, statically coverable or runtime-only; and a
meaningful amount of real dead code is found.

**Result**: 47 of the 60 findings in hand-written code are real dead code
(78%). Methods and never-read properties pass the 80% bar, never-written
properties do not:

- methods: 90%
- never-read properties: 83%
- never-written properties: 25%, from 8 findings
- constants: no findings left

The run found 45 dead members. 7 of the 13 false positives come from one
dead-code-detector limitation: a trait property that another declaration of the
same property shadows. The other 6 have callers in graphql-php and Symfony's
serializer. Each can be identified statically, so the run gives no reason to
build the Drush runtime dump.

The classification below was made by Claude, with evidence for each finding.
A dpl-web maintainer then reviewed every finding: see the
[maintainer review](#maintainer-review).

## Setup

- **Versions**: dpl-web `develop` at `ceeb29b243` (2026-10-02), with
  Drupal 11.4.7, PHPStan 2.2.14, phpstan-drupal 2.2.0 and
  shipmonk/dead-code-detector 1.4.2.
- **Environment**: the runs use `uselagoon/php-8.4-cli-drupal`, the base image
  of dpl-web's CLI container. This package is installed through a Composer
  `path` repository, and PHPStan's result cache is cleared before every run,
  because it cannot see code changes in a path repository.
- **Rerun**: on 2026-10-12, this package at `afacf8c`, after its code was
  restructured and the string-callable provider rewritten, reported the same
  60 findings.
- **Config**: `phpstan.dead-code.neon`, which is not committed to dpl-web:
  - Level 0. Only `shipmonk.*` errors are counted, with
    `tools/summarise-findings.php`.
  - Paths: `web/modules/custom`, `web/profiles/dpl_cms` and
    `web/themes/custom`, tests included.
  - All member kinds on, and this package's defaults otherwise.
- **Baseline**: dead-code-detector without this package, with
  dead-code-detector's own tests excluder pointed at
  `web/modules/custom/*/tests` and `web/modules/custom/*/*/tests`.
- **Generated code**: the GraphQL clients in `src/GraphQL/{Operations,Types}`
  and the `src/ProxyClass` proxies. It is analysed, so its calls count, but
  its findings are counted separately: 771 in every run.

## Runs

Findings in hand-written code, as providers were added:

| Run | Providers | Findings | Removed |
|---|---|---|---|
| 0 | dead-code-detector alone | 314 | |
| 1 | + library code | 269 | 45 |
| 2 | + `services.yml`, `routing.yml` | 147 | 122 |
| 3 | + `#[Hook]`, Drush, string callables, conventions, service tags, Twig, tests excluder | 69 | 78 |
| 4 | + concatenated callables (`static::class . ':method'`) | 68 | 1 |
| 5 | + property conventions and schema callbacks, from the [Canvas benchmark](canvas.md) | 62 | 6 |
| 6 | + `constantsReadByConvention` (`MyCLabs\Enum\Enum`) | 54 | 8 |
| 7 | − the `autowire_plugin_trait` provider, whose premise was wrong | 60 | 6 back |

Every removal was checked to be a caller Drupal really makes:

- **Library code** (45): overrides of members declared outside core's `lib/`:
  - graphql and graphql_compose plugins
  - Views plugins
  - openid_connect
  - media
  - Kernel tests' `$modules`
- **`services.yml` and `routing.yml`** (122):
  - 82 constructors of container-built services, routed controllers and forms
  - route controllers, title callbacks and access callbacks
  - service factories
- **Run 3** (78):
  - `#[Hook]` methods and hook class constructors
  - Drush commands
  - Form API `'::method'` callbacks and `service:method` strings
  - 23 GraphQL DataProducer `resolve()` methods and 6 REST resource
    HTTP-verb methods, through `methodsCalledByConvention`
  - access checkers (`access_check` tag)
  - a cache context that duck-types core's interface (`cache.context` tag)

  Twig matching alone keeps one member alive, `GoLinkParagraph::getUri()`.
  That is a coincidental name match: the templates' `.uri` is on `Url`
  objects. It is the expected cost of the conservative default, and the method
  is probably dead.
- **Run 5** (6): trait properties, which a provider for contrib's
  `autowire_plugin_trait` marked as written on the assumption that the module
  writes them by reflection. It does not: its `create()` passes services to
  the constructor by parameter name. Run 7 removed that provider, and the six
  are [false positives](#false-positives) of a dead-code-detector limitation.
- **Run 6** (8): `BlockedReason`'s constants, which `MyCLabs\Enum\Enum` reads
  through `__callStatic()` and `from()`. Since 2026-10-04, a usage provider
  in dpl-web's own PHPStan config covers them, because the package covers
  only Drupal.

A later fix to Twig parsing, for chained and numeric-segment accesses, changed
nothing here. Re-measured on 2026-10-05, after Twig matching came to read every
`*.twig` and static methods, it still keeps only `GoLinkParagraph::getUri()`
alive.

The plugin-discovery provider was not built. No remaining finding was a
plugin constructor or anything else plugin discovery explains: plugins have
`create()`, which the library-code rule covers.

## Classification of the 60 remaining findings

| Kind | Findings | Real dead code | False positives | Precision |
|---|---|---|---|---|
| Methods | 29 | 26 | 3 | 90% |
| Properties never read | 23 | 19 | 4 | 83% |
| Properties never written | 8 | 2 | 6 | 25% |
| Constants | 0 | 0 | 0 | |
| **All** | **60** | **47** | **13** | **78%** |

### False positives

None of the 13 is a Drupal mechanism that a provider is missing.

| Cause | Findings | Remedy |
|---|---|---|
| A trait property that another declaration of the same property shadows: in a trait composing it, in the class using it, or in that class's parent. PHP merges the declarations into one property; dead-code-detector keeps them apart and attributes reads and writes to one of them. `FileTrait`'s `$entityTypeManager`, `$fileSystem` and `$fileRepository`, and `$entityTypeManager` in `ImageTrait`, `SoundTrait` and `EmbedVideoTrait` | 1 never read, 6 never written | Drop the redundant declarations; otherwise a dead-code-detector limitation |
| graphql-php's default field resolver reads response objects' properties by field name (`ImportResponse`, `NewContentResponse`) | 3 never read | `@api` on the classes |
| Symfony's serializer constructs `dpl_campaign`'s `Facet`, `FacetValue` and `Query` | 3 methods | Upstream in dead-code-detector's Symfony support; `@api` meanwhile |

### Real dead code

Evidence for each: the member is not referenced anywhere in custom code,
templates, YAML or tests, and does not override anything Composer installed.

**Injected services that are never used** (18 properties, 20 findings):

- `bnf_client`: `BnfExporter::$httpClient`, `BnfExporter::$translation`,
  `SubscriptionHelper::$scheduler`, `DevelopmentCommands::$scheduler`
- `bnf`: `BnfMapperNodePluginBase::$logger`,
  `ParagraphNavGridManualMapper::$mapper`,
  `ParagraphNavSpotsManualMapper::$mapper`
- `dpl_breadcrumb`: `BreadcrumbHelper::$languageManager`
- `dpl_campaign`: `MatchResource::$configManager` and
  `MatchResource::$handyCacheTagManager`, both set in `create()` and never
  read
- `dpl_consumers`: `DplConsumersCommands::$entityTypeManager`
- `dpl_event`: `CopyInheritedForm::$entityTypeManager`,
  `CopyInheritedForm::$logger`
- `dpl_fbi`: `CqlSearchWidget::$moduleHandler`, which is never read nor
  written
- `dpl_library_agency`: `GeneralSettingsForm::$reservationSettings`
- `dpl_po`: `DplPoCommands::$source` (never read nor written) and
  `DplPoCommands::$destination`
- `dpl_redia_legacy`: `RssFeeds\EventsController::$fileUrlGenerator`

**`DplReactConfigInterface::getConfig()`** (16 methods):

- **Who calls it**: the settings services are injected as the interface, but
  the only call to `getConfig()` is on `DplInstantLoanSettings` itself, which
  is not reported.
- **What is reported**: the interface method, `DplReactConfigBase`'s abstract
  method, and 14 implementations:
  - `DplBiblioSettings`, `DplDashboardSettings`, `DplFavoritesListSettings`,
    `DplFeesSettings`
  - `GeneralSettings`, `ListSizeSettings`, `DplLoansSettings`,
    `DplMenuSettings`
  - `DplPatronPageSettings`, `DplPatronRegSettings`, `DplPublizonSettings`,
    `DplRecommenderSettings`
  - `DplReservationsSettings`, `UniloginConfiguration`

**Other members** (11):

- `bnf_client`: `Subscription::setTags()`, `Subscription::setCategories()`,
  `SubscriptionHelper::deleteWithContent()`
- `dpl_campaign`: `FacetValue::$key`. The field is in the REST resource's
  documented input but is never read.
- `dpl_consumers`: `ConsumerRole::delete()`
- `dpl_library_agency`: `ReservationSettings::enableSmsNotifications()`
- `dpl_login`:
  - `AbstractUserTokensProvider::__construct()`: both subclasses define their
    own constructor without calling `parent::__construct()`
  - `LibraryAgencyIdProvider::getAgencyId()`: its service is never used
  - `UserTokens::create()`: a container-built service that does not implement
    `ContainerInjectionInterface`
- `dpl_update`: `MediaCleanup::getArchivedMediaIds()`
- `media_videotool`: `VideoToolMediaLibraryAddForm::getSourceFieldDefinition()`,
  which overrides nothing

## Success criteria

1. **Precision of at least 80%, per kind**: met, except for never-written
   properties.
   - methods: 90%
   - never-read properties: 83%
   - never-written properties: 25%. Six of the 8 findings are the trait
     limitation above.
   - constants: no findings left

Constants were at 0% until `constantsReadByConvention` handled
`MyCLabs\Enum\Enum`. Switching off a weak kind would not have worked: this
package cannot change dead-code-detector's defaults, because extension-installer
loads dead-code-detector's `rules.neon` after this package's `extension.neon`.
2. **Every false positive explained**: met. All 13 have a named cause, a
   dead-code-detector limitation or a library mechanism. None is runtime-only
   wiring.
3. **Meaningful real dead code**: met. There are 45 dead members in 47
   findings: 18 unused injected services, a 16-method unused interface
   contract, and 11 other members.

## Maintainer review

On 2026-10-04 a developer who knows dpl-web reviewed all 54 findings, one at
a time, with links to the code:

| Verdict | Findings | Members |
|---|---|---|
| Real dead code | 23 | The 16 unused injected services (17 findings); `AbstractUserTokensProvider::__construct()`; `UserTokens::create()`; `VideoToolMediaLibraryAddForm::getSourceFieldDefinition()`; `DplPoCommands::$source` (2) and `::$destination` |
| Unused, kept on purpose as API | 21 | `DplReactConfigInterface::getConfig()` and its 15 implementations; `Subscription::setTags()`, `::setCategories()`; `SubscriptionHelper::deleteWithContent()`; `LibraryAgencyIdProvider::getAgencyId()`, a service for modules webmaster sites may install; `MediaCleanup::getArchivedMediaIds()` |
| Points to another problem | 3 | `ConsumerRole::delete()`: probably a missing call in `ConsumerHandler::delete()` (deferred). `ReservationSettings::enableSmsNotifications()`: `GeneralSettingsForm` should call it instead of writing the config key. `FileTrait::$entityTypeManager`: it is read, but `ImageTrait` and `SoundTrait` needlessly redeclare it. |
| Unsure, possibly API | 1 | `FacetValue::$key`, a field of the REST input |
| Used: false positive | 6 | GraphQL response properties read by graphql-php's default resolver (3); input objects built by Symfony's serializer (3) |

The tool was right that nothing uses the member in 47 of the 54 findings
(87%), the estimate at the time. The other 7 are the 6 false positives and
`FileTrait::$entityTypeManager`.

The six never-written trait properties that run 7 brought back came after
this review. They are the redeclaration it found for
`FileTrait::$entityTypeManager`.

What this adds to the estimate:

- **Every false positive is outside Drupal's wiring.** They come from
  graphql-php, Symfony's serializer and a dead-code-detector trait limitation.
  No finding was code that Drupal calls through a mechanism this package misses.
- **26 findings lead to a code change**: 23 removals and 3 other fixes. Two
  of the linked ones resolve each other: making `GeneralSettingsForm` call
  `enableSmsNotifications()` uses its unused `$reservationSettings`.
- **21 findings are deliberate API.** dpl-web is a distribution, and webmaster
  sites may install modules that use its services and contracts. Marking those
  members `@api` is a one-time cost that stops them being reported again.

[dpl-web]: https://github.com/danskernesdigitalebibliotek/dpl-web
