# AGENTS.md

A PHPStan extension: Drupal usage providers for
[shipmonk/dead-code-detector][dcd]. The [README](README.md) says what it
does for users and why it works the way it does; this file is for changing
it.

## Documentation

- `README.md`: installation, what counts as used, configuration, limitations,
  and the short form of writing a provider.
- `docs/coverage.md`: Drupal's documented mechanisms mapped to the providers,
  with what is not covered. Change it with the code.
- `docs/extending.md`: how a site writes its own provider, and when a
  mechanism belongs here instead.
- `docs/twig.md`: Twig name matching, its limits and the measurement behind
  it.
- `docs/related-work.md` and `docs/measurements/`: context and evidence; they
  describe a point in time and say which. Only public corpora are named;
  client projects stay out of the documentation.

Verify every URL before writing it down, with a request, not from memory.

## Architecture

- `src/Core/<Mechanism>/` and `src/Contrib/<Mechanism>/`: one directory per
  Drupal mechanism, holding its usage provider and the pure decision code it
  needs. `Core` is Drupal core, `Contrib` is contrib modules and Drush, one
  module at a time.
- `src/Support/`: code tied to no Drupal API: wiring and settings file
  discovery, YAML reading, usage origins, the declared-usage and
  calls-by-name bases, and the Composer library-code rule. The two
  calls-by-name bases are also the extension points sites build on.
- `extension.neon`: service wiring. Users load it through
  phpstan/extension-installer. Its two parameters are the whole
  configuration; see the design for why there is no more.
- `tests/<Bucket>/<Mechanism>/`, mirroring `src/`: unit tests for decisions,
  and rule tests that run shipmonk's `DeadCodeRule` from the PHPStan container
  over fixtures in `data/`. Rule tests with the package's default parameters
  share one container, `tests/fixtures.neon`, and one analysis; their fixtures
  live in `Drupal\example\<Mechanism>`, so each test sees only its own. A
  test needing other parameters names its own neon, with paths from
  `%rootDir%`, PHPStan's directory.

## Conventions

- Keep reading the world separate from deciding about it. A reader is a
  service the container builds from its sources and injects; it reads once,
  on first use. A decision is a static function of data, `parse()` included,
  and carries `@phpstan-pure`, which PHPStan checks. Nothing static reads the
  world, and unit tests must not need the filesystem.
- `tests/Architecture` holds the phpat rules PHPStan runs: `Support` depends
  on no Drupal area, `Contrib` builds on `Support` alone, and readers are
  built by the container only. shipmonk's rules name providers
  `*UsageProvider` and excluders `*Excluder`.
- Name fixture classes and members after the case they prove.
- phpstan-drupal is a dependency for how it analyses Drupal code; do not
  build on its static maps. Use shipmonk only through its documented
  extension points.

## Cross-cutting

- **Result cache.** `DrupalFilesResultCacheMetaExtension` hashes every wiring
  and settings file the readers read, and each wiring file's path within the
  analysed tree, which the tests excluder depends on. Paths within the tree
  let the cache survive the checkout moving. PHPStan's result cache does not
  notice code changes in this package when a site installs it from a Composer
  `path` repository; clear it when developing against a site.
- **Discovery.** Wiring files are found under PHPStan's analysed paths minus
  its excluded paths, skipping `node_modules`, `vendor` and `.git`.
- **phpstan-drupal's maps are not used.** Its `ServiceMap` drops calls, tags,
  factories, configurators and the file a service came from, and its
  `ExtensionMap` ignores the analysed and excluded paths. Both are static
  state filled at bootstrap and due to change. Not building on them also
  keeps an upstream move possible.
- **Calls by name.** Each mechanism that calls members by name on subtypes
  has a provider of its own, as each framework does in shipmonk, so it can be
  as precise as the mechanism allows. They come from concrete findings: REST
  resources' HTTP methods and GraphQL data producers' `resolve()` on dpl-web;
  access checkers' `access()`, config entity properties, plugin attribute
  properties and constraint properties on Canvas; JSON:API resources'
  `process()` elsewhere.

## Adding a provider

1. One directory per mechanism, under `Core` or `Contrib`. The provider
   carries `@see` tags naming the core or module class that makes the call
   and the drupal.org page or change record that documents it.
2. Wiring files get a reader and a static `parse()`; conventions get a
   reflection-based provider, on the calls-by-name bases where they fit.
3. Register it in `extension.neon`, readers with `autowired: false`.
4. Tests: unit tests for the decision, and a rule test with fixtures in
   `Drupal\example\<Mechanism>` added to `tests/fixtures.neon`. A fixture
   class in a classmap-loaded file needs `composer dump-autoload`.
5. Add the row to `docs/coverage.md` and the mechanism to the README's list.

## Commands

Always use the make targets, and run the checks for every area you touch.

- `make lint-php`, `make analyse-php`, `make test-php`, `make format-php`
- `make lint-markdown`, `make lint-yaml`, `make lint-actions`
- `make checks`: everything CI runs
- `make dependencies-lowest`, `make dependencies-highest`: the oldest and the
  newest dependencies composer.json allows, which the CI "PHP" workflow tests
  on PHP 8.3 to 8.5.
- `make test-coverage`, `make test-mutation`: coverage with pcov, and
  Infection, in the image `Dockerfile` builds from the official one, since no
  official image ships a coverage driver and the host PHP may lack one.
  Infection mutates the decision classes, those carrying `@phpstan-pure`, and
  fails on any covered mutant the tests miss; `infection.json5` lists the
  mutants ignored, each with its reason. Keep that scope: readers and
  providers are reached only through rule tests sharing one analysis, so
  Infection cannot tell which test observes a mutant there and reports false
  escapes. `MUTATE="src/Core/Hook/HookMethods.php"` limits a run to files.
  The CI "PHP" workflow runs the mutation target on the runner's own PHP with
  `IN_DOCKER=`, so Infection's annotations reach the files. Neither target is
  part of `make checks`.

[dcd]: https://github.com/shipmonk-rnd/dead-code-detector
