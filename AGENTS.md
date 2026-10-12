# AGENTS.md

A PHPStan extension: Drupal usage providers for
[shipmonk/dead-code-detector][dcd]. Read [docs/design.md](docs/design.md)
before changing behaviour; it records what was decided and why.

## Architecture

- `src/Core/<Mechanism>/` and `src/Contrib/<Mechanism>/`: one directory per
  Drupal mechanism, holding its usage provider and the pure decision code it
  needs. `Core` is Drupal core, `Contrib` is contrib modules and Drush, one
  module at a time.
- `src/Support/`: code tied to no Drupal API: wiring and settings file
  discovery, YAML reading, usage origins, declared-usage and reflection bases,
  and the Composer library-code rule.
- `extension.neon`: service wiring. Users load it through
  phpstan/extension-installer.
- `tests/<Bucket>/<Mechanism>/`, mirroring `src/`: unit tests for decisions,
  and rule tests that run shipmonk's `DeadCodeRule` from the PHPStan container
  over fixtures in `data/`. Rule tests with the package's default parameters
  share one container, `tests/fixtures.neon`, and one analysis; their fixtures
  live in `Drupal\example\<Mechanism>`, so each test sees only its own. A
  test needing other parameters names its own neon, with paths from
  `%rootDir%`, PHPStan's directory.
- `docs/coverage.md`: Drupal's documented mechanisms mapped to the providers.
  Change it with the code.

## Conventions

- Keep reading the world separate from deciding about it. A reader is a
  service the container builds from its sources and injects; it reads once, on
  first use. A decision is a static function of data, `parse()` included, and
  carries `@phpstan-pure`, which PHPStan checks. Nothing static reads the world,
  and unit tests must not need the filesystem.
- `tests/Architecture` holds the phpat rules PHPStan runs: `Support` depends on
  no Drupal area, `Contrib` builds on `Support` alone, and readers are built by
  the container only. shipmonk's rules name providers `*UsageProvider` and
  excluders `*Excluder`.
- Name fixture classes and members after the case they prove.
- phpstan-drupal is a dependency for how it analyses Drupal code; do not
  build on its static maps. Use shipmonk only through its documented
  extension points. See [design](docs/design.md#cross-cutting).

## Commands

Always use the make targets, and run the checks for every area you touch.

- `make lint-php`, `make analyse-php`, `make test-php`, `make format-php`
- `make lint-markdown`, `make lint-yaml`, `make lint-actions`
- `make checks`: everything CI runs
- `make test-coverage`, `make test-mutation`: coverage with pcov, and
  Infection, in the image `Dockerfile` builds from the official one, since no
  official image ships a coverage driver and the host PHP may lack one.
  `MUTATE="src/Core/Hook/HookMethods.php"` limits the mutation run to files.
  Not part of `make checks`.

[dcd]: https://github.com/shipmonk-rnd/dead-code-detector
