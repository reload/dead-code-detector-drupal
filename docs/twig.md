# Twig templates

How the package counts members that Drupal templates use, what it does not
see, and why it works this way. The [design](design.md) covers the rest of
the package.

## How it works

Templates call methods on objects, but do not say what class a variable
holds. The provider therefore matches by name: a public member of any
analysed class counts as used if a template accesses its name, and the
Drupal sandbox lets templates call it.

## What it handles

- **Templates read:** every `*.twig` file under the analysed paths, such as
  `*.html.twig` and single-directory components' `<name>.twig`, minus
  PHPStan's `excludePaths`. Core and contrib templates are not read: they
  cannot call methods that exist only in custom code.
- **Accesses found:** every name after `.` or `?.`, found with Twig's own
  lexer, so Drupal's tags (`{% trans %}`) are no obstacle:
  - each access of a chain (`node.owner.name`) and after numeric segments
    (`items.0.title`);
  - in print and tag blocks, across lines, and in filter arguments,
    `include ... with`, `set`, `for` and macros;
  - not in markup, string literals, comments or `verbatim` blocks. A
    template Twig cannot lex yields nothing, as Drupal cannot render it
    either.
- **Members matched,** the way Twig resolves `x.foo`, ignoring case:
  - the public property `foo`, unless static, since Twig reads properties off
    the object;
  - the public method `foo`, and any public method `get`, `is` or `has`
    followed by `foo` (`getFoo`, `isfoo`, `GetFoo`), static ones included,
    since Twig calls them through the object.
- **Sandbox:** only methods Drupal's `TwigSandboxPolicy` lets templates call
  count. By default these are:
  - the methods `id`, `label`, `bundle`, `get`, `toString` and `__toString`;
  - methods starting with `get`, `has` or `is`, case-sensitively, as in core;
  - methods with `#[TwigAllowed]`;
  - any method of `Attribute` and `Cva`, and of their subclasses.
- **Site settings:** the sandbox lists come from the site's
  `twig_sandbox_allowed_classes`, `_methods` and `_prefixes` settings, read
  statically from the PHP files in `sites/*/`. Those include `settings.php`
  and the files it includes next to it. The Drupal root is where Composer
  installed `drupal/core`.
  - A list a site sets replaces core's default, as in Drupal.
  - Lists from several assignments are combined, because includes and
    conditions decide which one wins.
- **Inherited members:** a member is marked on the class that declares it.
- **Tests:** a usage records the templates it comes from. If all of them are
  under a `tests` directory, the tests excluder treats it as test code.
- **Cache and switch:** templates and settings files are part of the
  result-cache hash. With `drupalDeadCode.twigTemplates.enabled: false`,
  neither is found, read nor hashed.

## What it does not handle

- **The type of a variable.** Any class with a matching member counts, so a
  coincidental name keeps a dead member alive. dpl-web's
  `GoLinkParagraph::getUri()` matches a `.uri` on core `Url` objects.
- **Names that are not written after a dot:** subscripts (`item['foo']`),
  `attribute(item, 'foo')`, and dynamic names (`item[name]`). A member only
  reached that way is reported dead.
- **Templates outside the analysed paths,** and template strings in PHP,
  such as `inline_template` render elements.
- **Sandbox lists computed at runtime,** for example from an environment
  variable. Core's default applies instead.
- **Twig's precedence.** Twig calls only one of `foo()`, `getFoo()`,
  `isFoo()` and `hasFoo()`, skipping `hasFoo()` when `isFoo()` exists, and
  reads a property before calling a method. All of them count, which can keep
  the one Twig does not call alive.

## Values and arrays passed to templates

Most Drupal templates do not get objects. Preprocess functions and render
arrays pass scalars and arrays, and the template reads keys. The themed
Reload projects have 17 to 43 preprocess hooks each, dpl-web has 60, and
they pass values like these:

```php
$variables['url'] = $node->toUrl()->toString();
$variables['title'] = $variables['content']['link'][0]['#title'];
```

- **The method calls happen in PHP,** where shipmonk sees them. Procedural
  `*_preprocess_*` functions in `.module` and `.theme` files are analysed,
  as phpstan-drupal adds those extensions, and are entry points.
  `#[Hook('preprocess_...')]` methods count as hooks. Render arrays built in
  controllers or builders (`'#items' => $this->getItems()`) are the same.
- **What the template reads are array keys,** not class members, so there is
  nothing to mark and nothing to miss. This is why Twig matching keeps so
  little alive.
- **Untyped values in preprocess:** `$variables['node']` is `mixed` to
  PHPStan, so `$variables['node']->getFoo()` keeps every method named
  `getFoo` alive, by shipmonk's default. With `usageOverMixed`, such calls
  give false positives instead: 3 on the Reload projects, and none hidden.
  A `@var` annotation or an `instanceof` check types the call.
- **Array keys make name matching less precise,** not more useful. A key such
  as `.uri` or `.title` can match a class member by coincidence.
- **Not covered:** a variable that preprocess computes but no template
  reads. It is dead code in an array key, not a class member, so shipmonk
  cannot report it. Finding it would mean pairing `$variables` assignments
  with template accesses per theme hook, the same theme-hook modelling that
  makes typing templates expensive.

## Measured value

Switching Twig matching off on every benchmark corpus (2026-10-05) changes
almost nothing:

| Corpus | Templates | Distinct names accessed | Members only Twig keeps alive |
|---|---|---|---|
| 5 decoupled Reload projects | 0 | 0 | 0 |
| 5 themed Reload projects | 108–174 each | about 115 each | 0 |
| dpl-web | 193 | 130 | 1, a coincidental match |
| Canvas | 114 | 14 | 0 |

Templates rarely call custom methods. A third to almost half of the names
are fields (`field_*`, read through core's magic getters). Most of the rest
are core API (`addClass`, `bundle`, `isPublished`, `getString`, `label`) or
render-array keys and component props (`content`, `title`, `src`). The one
member kept alive, dpl-web's `GoLinkParagraph::getUri()`, matches a `.uri`
on core `Url` objects and is probably dead.

## Decision

Keep cheap name matching, on by default. It costs about one hidden
finding per site. It guards the case none of the corpora exercises much: a
bundle class whose custom getter only templates call, which would otherwise
be reported dead. That follows [when unsure, code is
used](design.md#when-unsure-code-is-used).

Typing template variables, as [TwigStan][twigstan] does, is rejected for
now. TwigStan compiles templates to PHP and types their variables from the
render calls that pass context, as Symfony controllers do; it calls itself
very experimental. Drupal templates get their variables indirectly, so typing
`node.getFoo()` would mean modelling:

- `hook_theme()` definitions and every preprocess function, of modules and
  themes;
- render elements such as `#node`;
- theme suggestions (`node--article--teaser`) and theme overrides;
- bundle classes, to know that `node` in `node--article` is `Article`.

On these corpora that would gain at most one finding. If precision ever
matters, the cheaper step is to scope names by the variables of entity
templates: `node.x` in `node--*` only matches classes implementing
`NodeInterface`, and likewise for paragraphs, media, terms and users, with
name matching for everything else. Benchmark a site that calls bundle-class
getters from templates before building either.

## How it got here

- Wiring is [read statically](design.md#wiring-is-read-statically), and
  [when unsure, code is used](design.md#when-unsure-code-is-used), so name
  matching came first. shipmonk's own Twig support covers Twig extension
  callbacks and Symfony's `#[Template]` view data, neither of which reaches
  Drupal's templates.
- The first version matched `*.html.twig` with a regular expression. Twig's
  lexer replaced it, so strings, comments and `verbatim` blocks no longer
  yield names and `?.` does. Reading every `*.twig` brought in
  single-directory components.
- The sandbox lists were first package parameters. They are now read from
  the site's settings, so no configuration can drift from the site.
- Static methods, and getter prefixes in any casing, were added to match
  what Twig itself calls.
- Typing template variables was considered and rejected; see the
  [measurement](#measured-value) and the [decision](#decision).

[twigstan]: https://github.com/twigstan/twigstan
