# Coverage

How Drupal's developer documentation says Drupal calls custom code, mechanism
by mechanism, and what covers each one: a provider of this package, shipmonk
itself, or nothing yet. The [README](../README.md) explains what the
providers do; this page is the map from the documentation to them.

Use it when the documentation changes, a Drupal release adds a mechanism, or a
finding turns out to be a false positive: find the row, and change the row
with the code.

Sources: the API topics in core's `*.api.php` files (Drupal 11.4.8), the
[developer guides on drupal.org][docs], the change records they link, and the
[Drush 13 documentation][drush], surveyed on 2026-10-12. Each row was checked
against the providers, and the uncertain ones against shipmonk in the rule
tests' PHPStan container.

How to read the tables:

- **Shape**: the documented code, as a developer writes it.
- **Reaches**: the member Drupal calls or reads, and how.
- **Covered by**: a class of this package, a shipmonk built-in, or the
  [library-code rule](../README.md#what-counts-as-used) when the member
  overrides a declaration in core or contrib. *Not reported* means shipmonk
  never reports that kind of member, so there is nothing to cover.
- **Tests**: the directory under `tests/` with the fixtures.

## Services and dependency injection

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| `*.services.yml`: `class`, `arguments`, `autowire` | the constructor, by the container | `Core\Services\ServiceUsageProvider` | `Core/Services` |
| `factory`, `calls`, `configurator` | the named methods | `Core\Services\ServiceUsageProvider` | `Core/Services` |
| tags `access_check` (`method`), `cache.context`, `service_collector` (`call`) | methods core calls without an interface | `Core\Services\ServiceUsageProvider` | `Core/Services` |
| other tags: `event_subscriber`, `paramconverter`, `breadcrumb_builder`, `theme_negotiator`, `normalizer`, `logger`, `stream_wrapper`, `http_middleware`, `access_policy` and the rest | interface methods | library rule | `Support/LibraryCode` |
| `http_client_middleware` | `__invoke()` | not reported: a magic method | |
| `getSubscribedEvents()` returning `'onFoo'`, `['onFoo', 10]` or `[['onFoo'], ['onBar', 5]]` | the named methods | shipmonk's Symfony support | |
| `ContainerInjectionInterface::create()` written out with `new static(...)` | the constructor | shipmonk | |
| `create()` inherited from core: `AutowireTrait`, also through `FormBase` and `ControllerBase` | the constructor, by `new static(...)` in a trait PHPStan does not analyse | **not covered**, unless a row below names the class | |
| `Drupal\<module>\<Module>ServiceProvider` | `register()`, `alter()` | library rule | |
| `#[AsCommand]` console commands (11.4) | the constructor, `execute()` or `__invoke()` | shipmonk's Symfony support | |

## Routing, menus and access

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| `_controller`, `_title_callback`, `_custom_access` as `Class::method`, `service:method` or an invokable class | the method, and the constructor by the class resolver | `Core\Routing\RouteUsageProvider` | `Core/Routing`, `Core/Services` |
| `_form` | the constructor; the form methods are overrides | `Core\Routing\RouteUsageProvider` | `Core/Routing` |
| `route_callbacks` | the method | `Core\Routing\RouteUsageProvider` | `Core/Routing` |
| `#[Route]` on controllers (11.4) | the routed methods; the constructor | shipmonk's Symfony support; `Core\Routing\RouteAttributeUsageProvider` | `Core/Routing` |
| `_entity_form`, `_entity_list`, `_entity_view`, `_entity_access` | the entity handlers' methods | library rule; a config-entity form's constructor as for `AutowireTrait` above | |
| `access_check` services registered in PHP | `access()` | `Core\Access\AccessCheckerUsageProvider` | `Core/Access` |
| `*.permissions.yml` `permission_callbacks` | the method and the constructor | `Core\Permission\PermissionCallbackUsageProvider` | `Core/Permission`, `Core/Services` |
| `*.links.menu.yml`, `*.links.task.yml`, `*.links.action.yml`, `*.links.contextual.yml`: `class`, `deriver`, `form_class` | plugin methods | library rule | |
| param converters, route subscribers, enhancers, filters, path processors | interface methods | library rule | |

## Hooks

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| `#[Hook]` on a method, on a class with `method:`, or on a class with `__invoke()` | the method; the constructor, as the class becomes an autowired service | `Core\Hook\HookUsageProvider` | `Core/Hook` |
| preprocess hooks (11.2), requirements hooks (11.2), hook classes in themes (11.3), Hux's `#[Hook]`, `#[Alter]` and `#[ReplaceOriginalHook]` | the same | `Core\Hook\HookUsageProvider` | `Core/Hook` |
| `hook_theme()` `'initial preprocess'` (11.2) as `static::class . ':method'`, `'Class::method'` or `'service:method'` | the method | `Core\Callable\StringCallableUsageProvider` | `Core/Callable` |
| the same as a literal `'Class:method'` with one colon | the method, and the constructor if the method is not static | `Core\Callable\StringCallableUsageProvider` | `Core/Callable` |
| procedural hooks, `hook_update_N`, post-update, deploy and install hooks | functions | not reported: functions are entry points | |
| `#[LegacyHook]`, `#[ReorderHook]`, `#[RemoveHook]`, `#[TrustedCallback]`, `#[TwigAllowed]` | nothing new: they allow, order or remove calls | nothing to mark | |

## Form, Render, Ajax and Batch API callbacks

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| `[$this, 'method']`, `[static::class, 'method']`, `[Foo::class, 'staticMethod']` in `#submit`, `#validate`, `#element_validate`, `#process`, `#after_build`, `#pre_render`, `#post_render`, `#lazy_builder`, `#access_callback`, `#ajax`, batch operations | the method | shipmonk | |
| `'::method'`, `'Class::method'`, `'Class:method'`, `'service:method'`, `Foo::class . '::method'` in the same keys | the method, and the constructor when the class resolver instantiates the class for a non-static method | `Core\Callable\StringCallableUsageProvider` | `Core/Callable` |
| `#[FormElement]` and `#[RenderElement]` plugins, `#value_callback` | `getInfo()`, `valueCallback()`, and the callbacks above | library rule; shipmonk | |
| `trustedCallbacks()` | an override; the methods it lists are reached as above | library rule | |
| forms built in PHP with `formBuilder()->getForm(Class::class)` | the constructor, by the class resolver | shipmonk when `create()` is written out; otherwise as for `AutowireTrait` above | |
| `[Foo::class, 'instanceMethod']` | nothing: PHP cannot call it | reported by shipmonk, which is right | |

## Plugins

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| plugin classes: `build()`, `create()`, constructors overriding `PluginBase` | overrides | library rule; shipmonk | `Support/LibraryCode` |
| custom attribute classes' properties | read by `AttributeBase::get()` | `Core\Plugin\PluginAttributePropertyUsageProvider` | `Core/Plugin` |
| custom annotation classes' properties | written by the parser, defaults read by reflection | **not covered** | |
| derivers with `ContainerDeriverInterface::create()` | the constructor, by `new static(...)` | shipmonk | |
| derivers with a constructor and no `create()` | the constructor, by `new $class($base_plugin_id)` | **not covered** | |
| `forms` on blocks, `form_class` on menu links, plugin form classes | `PluginFormInterface` methods; the constructor by the class resolver | library rule; the constructor as for forms above | |
| `*.layouts.yml`, `*.ckeditor5.yml`, `migrations/*.yml`: `class`, `deriver` | plugin methods | library rule | |

## Entities, fields and validation

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| entity classes: `baseFieldDefinitions()`, `preSave()`, `postLoad()` and the rest; bundle classes, with `#[Bundle]` (11.4) | static and instance overrides | library rule | |
| handlers: list builders, access handlers, view builders, route providers, views data, storage | `createInstance()` and overrides | library rule; shipmonk | |
| config entity properties, `config_export` | written on load, read on save | `Core\ConfigEntity\ConfigEntityPropertyUsageProvider` | `Core/ConfigEntity` |
| field types, widgets, formatters: `schema()`, `propertyDefinitions()`, `defaultSettings()`, `isApplicable()` | static overrides | library rule | |
| computed fields and properties set with `setClass()`, `computeValue()` | overrides | library rule | |
| constraints: options as public properties | written by `Constraint::__construct()`, unless the constructor has `#[HasNamedArguments]` | `Core\Validation\ConstraintPropertyUsageProvider` | `Core/Validation` |
| validators found as `<Constraint>Validator`, `validate()` | a class by name; an override | library rule | |
| `setDefaultValueCallback('Class::method')`, `setSetting('allowed_values_function', 'Class::method')` | a static method | `Core\Callable\StringCallableUsageProvider` | `Core/Callable` |
| `default_value_callback`, `allowed_values_function` in exported config YAML | a static method | **not covered** | |

## Configuration and recipes

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| `*.schema.yml` `Callback` constraints | a static method | `Core\ConfigSchema\SchemaCallbackUsageProvider` | `Core/ConfigSchema`, `Core/Services` |
| `*.schema.yml` `ClassResolver` constraint | `method` on a service or class | **not covered** | |
| `#[ActionMethod]` | the method, from recipes' `config.actions` | `Core\ConfigAction\ActionMethodUsageProvider` | `Core/ConfigAction` |
| `#[ConfigAction]` plugins, `config.factory.override` services | interface methods | library rule | |

## Theming and Twig

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| templates reading `foo.bar` and calling `foo.getBar()` within the sandbox | methods and public properties, by name | `Core\Twig\TwigUsageProvider` | `Core/Twig`, `Core/Settings` |
| `twig_sandbox_allowed_*` in `settings.php` | which methods templates may call | `Core\Settings\SettingLists` | `Core/Settings` |
| Twig extensions' `TwigFilter`, `TwigFunction`, `TwigTest` callables | the callables | shipmonk's Twig support | |
| `.theme` preprocess and suggestion functions | functions | not reported: functions are entry points | |
| single-directory components | templates as above; no PHP | `Core\Twig\TwigUsageProvider` | `Core/Twig` |

## Other APIs and contrib

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| REST resources' `get()`, `post()`, `patch()` and the other verbs | by the request method | `Core\Rest\RestResourceUsageProvider` | `Core/Rest` |
| JSON:API Resources' `process()` | by name | `Contrib\JsonApi\JsonApiResourceUsageProvider` | `Contrib/JsonApi` |
| GraphQL data producers' `resolve()` | by name | `Contrib\GraphQl\GraphQlDataProducerUsageProvider` | `Contrib/GraphQl` |
| exception subscribers' `on404()`, `on4xx()` | by name, from `HttpExceptionSubscriberBase` | `Core\EventSubscriber\HttpExceptionHandlerUsageProvider` | `Core/EventSubscriber` |
| Drush `#[CLI\Command]`, `#[CLI\Hook]`, `@command`, `@hook`; command constructors | the methods; the constructor | `Contrib\Drush\DrushUsageProvider` | `Contrib/Drush` |
| Drush 13.7 `#[AsCommand]` commands and `#[AsEventListener]` listeners | the constructor, `execute()`, `__invoke()` | shipmonk's Symfony support | |
| Drush `#[CLI\Complete(method_name_or_callable: ...)]`, deprecated | a method by name | **not covered** | |
| migrations' `callback` process plugin `callable:` | a static method named in YAML | **not covered** | |
| queue workers, mail, filter, image, search, media, workflow, CKEditor 5, layout and views plugins | overrides | library rule | |
| stream wrappers, normalizers, loggers, cache contexts | interface methods | library rule | |

## Tests

| Shape | Reaches | Covered by | Tests |
|---|---|---|---|
| test methods, data providers | | shipmonk's PHPUnit support | |
| `$modules`, `$defaultTheme`, `$profile`, `$strictConfigSchema` | read by the base class | library rule | |
| usages in `tests` directories | do not keep production code alive | `Core\TestCode\TestsUsageExcluder` | `Core/TestCode` |

## Attributes that do not count

An attribute keeps code alive only when Drupal calls the code because of it.
Core's `#[TrustedCallback]` and `#[TwigAllowed]` only allow a call,
`#[JsonSchema]` describes a return value, and `#[ReorderHook]` and
`#[RemoveHook]` act on other implementations, so none of them count.

Contrib attributes are supported one module at a time. On 2026-10-04, 299
distinct contrib modules were scanned: those installed in the measured sites,
and 191 widely used or attribute-heavy modules from drupal.org. Only two have
attributes that make Drupal call the method they are on: Hux, which offered
OOP hooks before core did and is supported, and Pinto, which builds theme
definitions from `#[ThemeDefinition]` methods and is not, since no measured
project uses it. For a module this package does not know, a site can use
`@api`, `ignoreErrors` or its own usage provider.

## Not covered

The rows marked **not covered**, in order of what they cost:

1. **Constructors reached only through core's `AutowireTrait::create()`**.
   PHPStan skips trait bodies outside the analysed files, so shipmonk never
   sees the `new static(...)`. It shows when nothing this package reads names
   the class: forms built with `getForm()`, config-entity forms, plugin form
   classes, validators with injection, and `\Drupal::classResolver()`.
   Content-entity forms, controllers on routes, hook classes, services and
   Drush commands are fine. The documentation tells developers to drop
   `create()`, so this grows.
2. **Annotation classes' properties** of custom plugin types. The
   documentation points to attributes since 10.2.
3. **String callables in YAML the package does not read**: migrations,
   exported field config, and the `ClassResolver` schema constraint. The PHP
   forms are covered.
4. **Derivers built with `new $class(...)`** and a constructor of their own.
5. **Form routes from `#[Route]`**, once core releases them
   ([#3584793][form-routes]): forms in a module's `Form` namespace found by a
   class-level `#[Route]`, whose constructors need the treatment controllers
   get.

Callers outside Drupal's wiring, such as Symfony's serializer and
graphql-php, are [known limitations](../README.md#callers-outside-drupals-wiring).

[docs]: https://www.drupal.org/docs/drupal-apis
[form-routes]: https://www.drupal.org/project/drupal/issues/3584793
[drush]: https://www.drush.org/13.x/
