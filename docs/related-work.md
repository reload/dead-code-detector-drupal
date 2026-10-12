# Related work

This page covers existing projects in the same space and how this package
relates to each. Facts are as of 2026-10-12.

## shipmonk/dead-code-detector

[Repository][dcd]. MIT.

A PHPStan extension that reports dead class members: methods, constants, enum
cases and properties. It detects dead cycles and transitively dead code, and
can remove dead code automatically. Built-in providers cover Symfony, Laravel,
Nette, Doctrine, Twig, PHPUnit and more. It has no Drupal support.

**Relation:** this package is a set of DCD usage providers and configuration.
DCD does the analysis.

**What we take:**

- Its extension points: `MemberUsageProvider`,
  `ReflectionBasedMemberUsageProvider`, and the
  `shipmonk.deadCode.memberUsageProvider` service tag.
- Its built-in Symfony, Twig and PHPUnit providers, which switch on in any
  Drupal site.
- The library-code provider generalises DCD's `VendorUsageProvider` from the
  vendor directory to every Composer install path. See the
  [README](../README.md#what-counts-as-used).
- Code copied from it is credited to it.

**Upstream:** DCD's README admits built-in support for frameworks. These are
the candidates to propose there:

- the install-path fix to `VendorUsageProvider`
- `MyCLabs\Enum\Enum` constants
- Symfony serializer denormalisation
- trait-composition attribution
- property redeclaration counting as a write
- built-in Drupal support

## mglaman/phpstan-drupal

[Repository][phpstan-drupal]. MIT.

The PHPStan extension for Drupal. It discovers extensions, autoloads
procedural files, types service lookups through a `ServiceMap`, and adds
Drupal-specific rules.

**Relation:** a dependency, for how it analyses Drupal code rather than for
its data.

- This package requires it. Without it, PHPStan analyses no procedural files
  and cannot load core modules' and contrib's classes. Its typing of
  `\Drupal::service()` and `$container->get()` gives DCD real call edges.
- We do not use its `ExtensionMap` or `ServiceMap`. The service map drops
  calls, tags, factories, configurators and the file a service came from,
  which our providers need, and the extension map ignores PHPStan's analysed
  and excluded paths. Both are static state, filled at bootstrap and partly
  `@internal`, and issue #1051 plans to replace them.
- It does not parse routing, plugin discovery or a hook registry.

**What we take:**

- The callback-resolution logic of `RenderCallbackRule`, which handles
  `Class::method`, `service:method` and `#[TrustedCallback]`. It is private,
  so it would be copied with attribution.
- Its `BootstrapResultCacheMetaExtension` as the pattern for invalidating the
  result cache when YAML files change.

## Drupal Canvas

[Project][canvas]. GPL-2.0-or-later.

A contrib module: the visual page builder in Drupal CMS. It adopted DCD in May
2026 ([#3589128][canvas-adoption]) with 12 hand-written providers in
`phpstan_rules/`, about 1,070 lines and untested. They took its zero-config
run from 467 errors to 0, and 25 genuinely dead members were removed.

**Relation:** prior art and a benchmark. The [benchmark](measurements/canvas.md)
runs this package on Canvas from before its adoption, without Canvas's
providers:

- **Recall is 100%.** Every dead member Canvas removed is still reported.
- **Most of its providers become unnecessary.** Of the 390 findings Canvas
  declared alive, all but 29 are explained.
- **The 29 left**: 26 are Canvas's own API-contract and trait blankets, and
  3 are in scope.
- **The benchmark drove changes.** It added config entity, constraint and
  plugin attribute property conventions, and schema callbacks, all mechanisms
  dpl-web barely uses.

Before that, its providers overlapped with this package only in part:

- **Overlap:** routing (`_controller` and `_title_callback`, marking every
  public method of a controller) and `#[Hook]` (method-level only).
- **Not in Canvas:** a library-code rule. Canvas relies on core's `lib/` paths
  matching DCD's vendor rule by accident, and adds targeted providers for core
  modules and contrib. Nor does it have providers for service constructors,
  plugins, Twig, Drush or `'::method'` callbacks.
- **Canvas-specific:** about half of its providers hardcode Canvas namespaces
  or classes.

**What we take:**

- Knowledge: its mechanism catalogue and its pitfalls. Those include stale
  results from the result cache, and an untyped `new $class()` that keeps
  every constructor alive.
- Its triaged findings, as a [benchmark](measurements/canvas.md).
- No code. Copying it would make this package GPL and close the upstream route
  into MIT-licensed DCD.

**Open threads:**

- [#3591672][is-called-by-drupal] proposes `#[IsCalledByDrupal(reason)]` for
  marking code in Canvas as used. This package would recognise a similar
  attribute if core adopts one.
- Canvas's maintainers have not been contacted. The benchmark shows this
  package covers Canvas's generic mechanisms, so Canvas could replace most of
  its providers with it.

## palantirnet/drupal-rector

[Repository][drupal-rector].

Rector rules for Drupal. `HookConvertRector` (since 0.21.0) converts
procedural hooks into `src/Hook/*Hooks.php` classes with `#[Hook]` attributes.
It leaves `#[LegacyHook]` shims behind and adds `autowire: true` service
entries.

**Relation:** the separate tool for migrating hooks to OOP. This package
reports dead code behind OOP hooks and does not warn about procedural hooks.
No tool warns about procedural hooks that could be converted. That was checked
in phpstan-drupal, Upgrade Status, drupal-check and Coder.

The `#[LegacyHook]` shims call `\Drupal::service(XHooks::class)->method()`.
That call keeps hook methods alive even without the `#[Hook]` provider.

## Laravel bridges on Packagist

Examples are `edalzell/dead-code-detector-laravel` and
`mjanser/dead-code-detector-patchlevel-event-sourcing`. They are DCD providers
for other frameworks, packaged separately. This package follows their naming
convention, `<vendor>/dead-code-detector-<framework>`, and reuses nothing else.

## Drupal core

These are parts of core we rely on, not tools:

- **`HookCollectorPass::checkForProceduralOnlyHooks()`**: the authoritative
  list of hooks that must stay procedural.
- **`TwigSandboxPolicy`**: which methods templates may call. It drives [Twig
  name matching](twig.md).
- **Deprecating `.module` files** ([#3619733][deprecate-module]) and
  **procedural hooks** ([#3487590][deprecate-procedural]) are open.

[canvas]: https://www.drupal.org/project/canvas
[canvas-adoption]: https://git.drupalcode.org/project/canvas/-/work_items/3589128
[dcd]: https://github.com/shipmonk-rnd/dead-code-detector
[deprecate-module]: https://www.drupal.org/project/drupal/issues/3619733
[deprecate-procedural]: https://www.drupal.org/project/drupal/issues/3487590
[drupal-rector]: https://github.com/palantirnet/drupal-rector
[is-called-by-drupal]: https://git.drupalcode.org/project/canvas/-/work_items/3591672
[phpstan-drupal]: https://github.com/mglaman/phpstan-drupal
