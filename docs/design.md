# Design

Why the package is built the way it is: the principles behind it, how its
code is structured, and how each part is checked and tested. It is for
contributors; what the package does for a site is in the
[README](../README.md). The rules themselves live in the configuration of
the tools that enforce them; this page says why those tools are there.

## Principles

- **Reuse before building.** Existing tools, rule sets and extension points
  come first: PHPStan's and PHPCS's own rules, dead-code-detector's documented
  extension points, and Drupal core's own classes in test fixtures. Something
  custom is built only when nothing existing can do the job, and the reason
  sits next to it. The providers are the main case: no existing tool knows how
  Drupal calls code. dead-code-detector is used only through its documented
  extension points, never its internals, so its minor releases do not break
  this package.
- **Read the site's files, never a running site.** Wiring, templates and
  settings are read from files under PHPStan's analysed paths. Drupal is not
  bootstrapped and no database is needed, so the analysis runs wherever
  PHPStan runs, CI included, and gives the same result for the same checkout.
  That is also how dead-code-detector supports other frameworks. Symfony's
  support reads the container file Symfony writes, but Drupal writes no such
  file, so its wiring files are read directly. What this misses is listed
  under the README's [limitations](../README.md#by-design).
- **When unsure, code is used.** A provider that cannot tell whether Drupal
  calls a member counts it as used, because a report is something people act
  on by deleting code; see the [README](../README.md#by-design).
- **The toolchain decides versions.** `composer.json` states what this
  package's code needs: the major version of each package it calls, and a
  higher floor only when the code uses something newer. dead-code-detector,
  PHPStan and Drupal core set the rest. PHP version follows from the Drupal
  versions supported. The lowest-dependency CI jobs install the oldest set
  these allow, so a floor that is too low fails there.

## Code structure

### Readers and decisions

Reading the world is kept apart from deciding about it.

- A **reader** is a service the PHPStan container builds and injects into
  the providers. It reads its files once, on first use, and hands the
  contents to a decision.
- A **decision** is a static function of data, such as a reader's `parse()`,
  marked `@phpstan-pure`. Nothing static reads the world.

The logic lives in the decisions, so it is tested with strings and arrays:
fast, without the filesystem, and one case at a time. PHPStan checks that a
decision reads nothing, and mutation testing can judge its tests. What is left
in a reader is the reading itself.

### Thin providers

Logic in a provider or excluder is never mutation-tested (see
[Checks and tests](#checks-and-tests)), so it moves out into a decision, and
the provider only connects a reader or a decision to dead-code-detector.

### Directories

- `src/Core/<Mechanism>/` and `src/Contrib/<Mechanism>/` hold one Drupal
  mechanism each: its provider and the decisions it needs. A mechanism's
  code is found in one place, and adding one leaves the others alone.
  - `Core` is Drupal core.
  - `Contrib` is contrib modules and Drush, covered one module at a time,
    built on Support's shared bases and never on code in `Core`.
- `src/Support/` is tied to no Drupal API: discovery of wiring and settings
  files, YAML reading, usage origins, the declared-usage and calls-by-name
  bases, and the library-code rule.

Each mechanism that calls members by name on subtypes has a provider of its
own on a calls-by-name base, so it can be as precise as that mechanism allows.
These providers are added for mechanisms found on real sites, not ahead of
need. The bases are also what a site builds its own provider on; see
[extending](extending.md).

## Checks and tests

| Part | Static checks | Tests | Mutation testing |
| --- | --- | --- | --- |
| Decisions | Purity, through `@phpstan-pure` | Unit tests without the filesystem | Yes |
| Readers | Built by the container only | Unit tests of their `parse()`; real files only where reading is the thing under test | Yes |
| Providers, excluders and the bases | Names tied to their interfaces | Rule tests over fixtures | No |

Every part is also analysed by PHPStan and PHPCS as configured in
`phpstan.neon.dist` and `.phpcs.xml.dist`.

- **Strict analysis.** PHPStan runs at the highest level with stock strict
  rule sets, to keep the code's quality and integrity high without rules of
  our own.
- **The structure is checked.** The layering and the readers' construction
  above are the design, and phpat enforces them; see
  `tests/Architecture/Layers.php`.
- **Rule tests.** A provider's work shows only in dead-code-detector's
  output, so rule tests run its `DeadCodeRule` from the PHPStan container over
  fixtures, against the Drupal core installed for development, and check one
  member per test.
  - Test classes with the same configuration share one analysis, so fixtures
    live in `Drupal\example\<Mechanism>`; see `DeadCodeRuleTestCase`.
  - A test needing other parameters names its own neon, with paths from
    `%rootDir%`.
- **Mutation testing.** Every mutant outside providers and excluders must
  die, so a surviving one is a missing test.
  - A mutant that cannot be killed is ignored in `infection.json5`, with the
    reason.
  - Providers and excluders are left out: their rule tests share an
    analysis, so Infection cannot tell which test observes a mutant and
    reports false escapes. This is also why logic moves out of providers.
  - `make test-mutation` picks its files by name, so phpat ties the names
    `*UsageProvider` and `*Excluder` to their interfaces, which keeps a
    decision from hiding under either name.
- **Independent tests.** Each test must pass on its own, so PHPUnit runs them
  in random order.
- **Fixtures name their case.** A fixture class or member is named after the
  case it proves, so a failure says what broke.

## Adding a provider

1. Make one directory per mechanism, under `Core` or `Contrib`.
   - The provider carries `@see` tags naming the core or module class that
     makes the call and the drupal.org page or change record that documents
     it.
2. Write the provider, with its logic in decisions.
   - Wiring files get a reader with a static `parse()`, listed in
     `Layers::READERS`.
   - The reader reads through `DrupalFiles`, with the files' suffix added
     there, so the result cache hashes them.
   - Conventions get a reflection-based provider, on the calls-by-name bases
     where they fit.
3. Register it in `extension.neon`.
4. Write unit tests for the decisions, and a rule test with fixtures in
   `Drupal\example\<Mechanism>` added to `tests/fixtures.neon`.
   - A fixture class in a classmap-loaded file needs `composer dump-autoload`.
5. Add the row to [coverage](coverage.md) and the mechanism to the README's
   list.
