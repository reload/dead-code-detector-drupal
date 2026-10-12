<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Twig;

use Drupal\Core\Template\Attribute as TemplateAttribute;
use Drupal\Core\Template\Attribute\TwigAllowed;
use ReflectionClass;
use ReflectionMethod;
use Reload\DeadCodeDetectorDrupal\Support\Reflection\ClassAncestry;

use function array_intersect;
use function in_array;
use function str_starts_with;

/**
 * Which methods Drupal's TwigSandboxPolicy lets templates call, given the
 * lists a site sets.
 *
 * @see \Drupal\Core\Template\TwigSandboxPolicy
 */
final class SandboxPolicy
{
    public const ALLOWED_CLASSES_SETTING = 'twig_sandbox_allowed_classes';
    public const ALLOWED_METHODS_SETTING = 'twig_sandbox_allowed_methods';
    public const ALLOWED_PREFIXES_SETTING = 'twig_sandbox_allowed_prefixes';

    /**
     * TwigSandboxPolicy's defaults, for settings a site does not set. Cva by
     * name: core requires twig/html-extra only since 11.4.
     */
    private const DEFAULT_ALLOWED_CLASSES = [TemplateAttribute::class, 'Twig\Extra\Html\Cva'];
    private const DEFAULT_ALLOWED_METHODS = ['id', 'label', 'bundle', 'get', '__toString', 'toString'];
    private const DEFAULT_ALLOWED_PREFIXES = ['get', 'has', 'is'];

    /**
     * @param list<string> $allowedClasses
     * @param list<string> $allowedMethods
     * @param list<string> $allowedPrefixes
     * @phpstan-pure
     */
    public function __construct(
        private readonly array $allowedClasses,
        private readonly array $allowedMethods,
        private readonly array $allowedPrefixes,
    ) {
    }

    /**
     * As in TwigSandboxPolicy, a list a site sets replaces core's default.
     *
     * @param array<string, list<string>> $settingLists
     *   Setting name => values, for the settings a site sets.
     * @phpstan-pure
     */
    public static function fromSettings(array $settingLists): self
    {
        return new self(
            $settingLists[self::ALLOWED_CLASSES_SETTING] ?? self::DEFAULT_ALLOWED_CLASSES,
            $settingLists[self::ALLOWED_METHODS_SETTING] ?? self::DEFAULT_ALLOWED_METHODS,
            $settingLists[self::ALLOWED_PREFIXES_SETTING] ?? self::DEFAULT_ALLOWED_PREFIXES,
        );
    }

    public function allows(ReflectionMethod $method): bool
    {
        if (in_array($method->getName(), $this->allowedMethods, true)) {
            return true;
        }

        foreach ($this->allowedPrefixes as $prefix) {
            if (str_starts_with($method->getName(), $prefix)) {
                return true;
            }
        }

        foreach ($method->getAttributes() as $attribute) {
            if ($attribute->getName() === TwigAllowed::class) {
                return true;
            }
        }

        return $this->isAllowedClass($method->getDeclaringClass());
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function isAllowedClass(ReflectionClass $class): bool
    {
        return in_array($class->getName(), $this->allowedClasses, true)
            || array_intersect(ClassAncestry::typeNames($class), $this->allowedClasses) !== [];
    }
}
