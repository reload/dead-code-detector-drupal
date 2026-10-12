# Measurements: Reload's Drupal 11 projects

This benchmark runs the package on Reload's Drupal 11 site projects: every
repository that was pushed to since 2026-04-01 and whose Drupal site is on
Drupal 11. They are ordinary client sites, unlike [dpl-web](dpl-web.md) and
[Canvas](canvas.md). The repositories are private, so this report names no
project, module or class.

**Result**: 22 of the 23 findings are members that nothing uses (96%). Every
kind of member passes the 80% bar. That is after adding two contrib
mechanisms the projects use. Before those, 22 of 39 were (56%).

- **[Hux][hux] hooks** caused 15 of the 17 false positives. Hux is a
  contrib module that provided OOP hooks before core did, and 4 of the 10
  projects still use it. Its `#[Hook]`, `#[Alter]` and `#[ReplaceOriginalHook]`
  now count like core's `#[Hook]`.
- **[JSON:API Resources][jsonapi-resources]** caused 1. Its controller calls
  `process()` on resource classes, which a provider now covers.
- **One false positive remains.** A contrib field formatter calls `label()`
  on plugins after checking `method_exists()`.
- **There is little dead code to find.** The projects total about 24,000
  lines of custom PHP, and all of them run PHPStan already, half at level 9
  or max. The common finding is a service that is injected and never read.

## Setup

- **Projects**: 10, in three groups. Four sites share one set of modules (A1
  to A4), five share another (B1 to B5), and one stands alone (C). A finding
  on a shared member counts once. So do the lines of a file that is identical
  in several projects.
- **Versions**: Drupal 11.3.17 to 11.4.8, phpstan-drupal 2.x and
  shipmonk/dead-code-detector 1.4.2. Four projects were on PHPStan 1.12 with
  `drupal/core-dev` 11.2, and were upgraded locally to PHPStan 2 with
  phpstan-drupal 2 and their other PHPStan extensions, in one `composer
  require -W`.
- **Environment**: `uselagoon/php-8.4-cli-drupal`, with this package installed
  through a Composer `path` repository. Nothing was committed to the projects.
- **Config**: level 0, and all custom modules, themes and profiles, tests
  included. The projects' own PHPStan configs were left out: some exclude
  tests, and some name frontend build directories that do not exist in a
  fresh checkout, which PHPStan rejects.
- **Baseline**: shipmonk without this package, with shipmonk's tests excluder
  pointed at the `tests` directories.
- **Cost**: each analysis takes about 5 seconds per project.

## Runs

| Project | Lines of custom PHP | shipmonk alone | With the package |
|---|---|---|---|
| A1 | 2,843 | 6 | 0 |
| A2 | 4,777 | 18 | 5 |
| A3 | 6,239 | 8 | 2 |
| A4 | 4,495 | 14 | 3 |
| B1 | 1,340 | 27 | 8 |
| B2 | 2,192 | 36 | 12 |
| B3 | 2,255 | 36 | 12 |
| B4 | 1,647 | 28 | 7 |
| B5 | 1,533 | 29 | 8 |
| C | 4,463 | 7 | 3 |
| **Distinct** | **23,675** | **87** | **23** |

"Distinct" counts shared files and members once.

The package explains 64 of shipmonk's 87 distinct findings, and each is a
caller Drupal really makes:

- core `#[Hook]` and Hux hook classes, with their constructors
- routes, forms and their constructors, and service constructors
- a Drush command
- Form API `'::method'` callbacks
- overrides of classes in core modules and contrib, which Composer installs
  outside `vendor/`, so shipmonk does not treat them as library code. They
  include migrate process plugins, Paragraphs behaviours, JSON:API Extras
  enhancers, computed fields, an image effect and an oEmbed provider.
- plugin attribute properties, and a JSON:API resource's `process()`

## Classification of the 23 remaining findings

| Kind | Findings | Nothing uses it | False positives | Precision |
|---|---|---|---|---|
| Methods | 6 | 5 | 1 | 83% |
| Properties never read | 12 | 12 | 0 | 100% |
| Properties never written | 2 | 2 | 0 | 100% |
| Constants | 3 | 3 | 0 | 100% |
| **All** | **23** | **22** | **1** | **96%** |

The classification was made by Claude, by searching each project for
callers, core and contrib included. No project maintainer has reviewed it.

**Nothing uses these 22:**

| Cause | Findings |
|---|---|
| A service is injected and stored, and the property is never read | 9 |
| A value object field that nothing reads | 1 |
| Constants for a field's allowed values that nothing references | 3 |
| Interface methods that are only called on implementations | 5 |
| Annotation keys documented as properties | 4 |

Two of these causes are a matter of judgement:

- **Interface methods called only on implementations.** DCD reports an
  interface method that nothing calls through the interface, [by
  design][dcd-interface]. Here, one interface redeclares three methods of a
  core interface that its implementations also implement. Its other two
  methods are called on the base class instead of the interface. A team may
  keep such methods to hold implementations to a contract.
- **Annotation keys documented as properties.** A plugin annotation class
  declares its keys as public properties, the way core's annotation classes
  do. Nothing reads or writes them: core's annotation parser passes the
  values to the constructor, and reflects on public properties only when
  there is no constructor. Converting the annotation to a plugin attribute
  makes the properties real, because `AttributeBase::get()` reads them.

**The false positive** is a plugin's `label()`. The plugin interface does not
declare it, but a contrib field formatter calls it after checking
`method_exists()`. The remedies are those for [callers outside Drupal's
wiring](../design.md#callers-outside-drupals-wiring).

## Calls on values of unknown type

By default, a call on a value of unknown type keeps every method of that name
alive. With shipmonk's `usageOverMixed` excluder on, 3 more findings appear,
all in A3. All three are false positives: methods called through an untyped
property. So untyped calls hid no dead code in these projects, and the
default is the right one for them.

## Twig name matching

Five projects are decoupled and have no templates. The other five have 108
to 174 templates each in custom code. Switching Twig matching off changes no
finding in any project: no member is kept alive by templates alone. See [Twig
name matching](../twig.md#measured-value).

[dcd-interface]: https://github.com/shipmonk-rnd/dead-code-detector#interface-methods
[hux]: https://www.drupal.org/project/hux
[jsonapi-resources]: https://www.drupal.org/project/jsonapi_resources
