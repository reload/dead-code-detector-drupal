# Measurements: Drupal Canvas

This benchmark runs the package against [Drupal Canvas][canvas]. Canvas's
maintainers already triaged every dead-code finding when they adopted
shipmonk/dead-code-detector ([#3589128][canvas-adoption]), so their decisions
serve as labels.

**Result**:

- **Recall is 100%.** Of the dead members Canvas removed and dead-code-detector
  reports, the package still reports all 24.
- **Most of Canvas's own providers become unnecessary.** Of the 390 findings
  Canvas declared alive, the package explains all but 29.
- **The 29 that remain:** 26 come from Canvas-specific blanket rules (its API
  contract and its traits), which the plan excludes from the score. That
  leaves 3 in scope.
- **One inline ignore becomes unnecessary.** It was a callback that Canvas
  could only ignore by hand.

## Setup

- **Canvas version**: Canvas at `d0aec309` (2026-05-12), the commit before
  adoption. It is checked out as a custom module, not installed by Composer,
  because this package treats Composer-installed code as library code.
- **Site**: Drupal 11.4.8 (`drupal/recommended-project`), with `drupal/core-dev`
  and Canvas's dev dependencies: ai, ai_agents, metatag, pathauto, search_api,
  simple_oauth, jsonapi_menu_items, drush and the OpenAPI libraries.
- **Tools**: PHPStan 2.2, phpstan-drupal 2.x, shipmonk/dead-code-detector
  1.4.2, run in `uselagoon/php-8.4-cli-drupal`.
- **Rerun**: on 2026-10-12, this package at `afacf8c`, after its code was
  restructured and the string-callable provider rewritten, reported the same
  61 findings with the same score.
- **Config**: Canvas's own PHPStan settings without its usage providers. That
  means its paths, excludes and phpstan-drupal entity mapping, at level 0.
- **Tests**: tests count as usage, so this package's tests excluder is off.
  Canvas's maintainers made their decisions on that basis.
- **Labels**, from the adoption commit `f9a8591`:
  - **Removed**: 28 deleted declarations count as real dead code.
  - **Ignored**: 5 inline `@phpstan-ignore shipmonk.*` count as deliberately
    kept.
  - **Alive**: every other finding counts as alive, by Canvas's providers or
    its path-wide ignores.
  - **Unscored**: findings in `canvas_ai` and `canvas_personalization`, which
    Canvas excluded from its cleanup.

## Runs

| | dead-code-detector alone | With this package |
| --- | --- | --- |
| All findings | 468 | 61 |
| Removed by Canvas (real dead code) | 24 | 24 |
| Ignored inline by Canvas | 5 | 4 |
| Alive according to Canvas | 390 | 29 |
| In `canvas_ai` and `canvas_personalization` (not scored) | 49 | 4 |

dead-code-detector alone reproduces Canvas's own zero-config run: 468 findings
against Canvas's recorded 467, with never-read and never-written properties and
constants matching exactly. That confirms the setup.

The 4 removed declarations that dead-code-detector does not report are dead
because of other removals, for example the constructor of a class whose only
purpose was injecting a dead property.

The ignore the package makes unnecessary is `Page::getRequestTime()`, a
base-field default value callback built as `static::class .
'::getRequestTime'`. Concatenated callables cover it.

## What this benchmark changed in the package

Canvas uses Drupal mechanisms that dpl-web barely does, and this benchmark
exposed them:

| Mechanism | Findings | Change |
| --- | --- | --- |
| Config entity properties, written by `ConfigEntityBase::__construct()` and read by `toArray()` | ~38 | Config entity property provider |
| Validation constraint options, written by `Constraint::__construct()` | ~16 | Constraint property provider |
| Plugin attribute properties, read by `AttributeBase::get()` | ~8 | Plugin attribute property provider |
| Config schema `Callback` constraints | 3 | New `*.schema.yml` reader |
| Access checker registered in PHP by `CanvasServiceProvider` | 1 | Access checker provider: `access()` |
| PHPUnit test methods and data providers | ~770 | None in the package. The benchmark site lacked `phpunit/phpunit`, and dead-code-detector's PHPUnit provider only switches on when it is installed. |

Switching Twig matching off changes no finding. Canvas's 114 templates
access 14 distinct names, mostly component props such as `src` and `alt`.

## The 29 findings Canvas considers alive

- **Canvas's API contract** (24, not scored): interface and abstract methods
  that only Canvas's `CanvasApiContractUsageProvider` keeps alive:
  - `VersionedConfigEntityInterface`, `CanvasAssetInterface`,
    `AdapterInterface` and `AdapterBase`
  - `PropSourceBase`, `ComponentCandidatesDiscoveryInterface` and its
    implementations
  - and others

  For a site, an interface method nobody calls through the interface is worth
  reporting. Canvas chose to keep its contract instead.
- **Canvas's traits** (2, not scored): methods that only
  `CanvasTraitUsageProvider`'s blanket rule keeps alive.
- **In scope** (3):
  - `UriTargetMediaTypeConstraint::$mimeType` is never read: the constructor
    reads `$options['mimeType']` instead. It is probably real dead code, and
    Canvas's blanket rule over constraint properties kept it.
  - `AutoSaveConflictConfigTestBase::$updateKey` and
    `PropSourceMatcherTestBase::$testedPropSourceMatcherClass` are never
    written. Subclasses redeclare them with defaults, and dead-code-detector
    does not count a redeclaration as a write to the parent's property. That is
    a dead-code-detector limitation.

## Precision against Canvas's decisions

There are 31 findings in scope: 24 removed, 4 ignored and 3 others.

- **Counting Canvas's deliberately kept findings as wrong**: 24 of 31 are
  real dead code, 77%.
- **Counting them as correct but kept**: 28 of 31, 90%. Canvas's own notes
  describe them as unused but kept: a constant for a fixture script outside
  the analysis, and an `assertSame()` shim.
- **Counting `$mimeType` as dead**: one more true positive.

[canvas]: https://www.drupal.org/project/canvas
[canvas-adoption]: https://git.drupalcode.org/project/canvas/-/work_items/3589128
