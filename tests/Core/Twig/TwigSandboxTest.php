<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Reload\DeadCodeDetectorDrupal\Tests\Core\Twig\Sandbox\ClassOnAllowList;
use Reload\DeadCodeDetectorDrupal\Tests\Core\Twig\Sandbox\SubclassOfClassOnAllowList;
use Reload\DeadCodeDetectorDrupal\Tests\Core\Twig\Sandbox\SubclassOfClassOnCoreAllowList;
use Reload\DeadCodeDetectorDrupal\Tests\Core\Twig\Sandbox\TemplateVariable;
use Reload\DeadCodeDetectorDrupal\Core\Twig\SandboxPolicy;

/**
 * @phpstan-type MethodReference array{class-string, string}
 */
final class TwigSandboxTest extends TestCase
{
    public function testMethodOnAllowListIsAllowed(): void
    {
        self::assertTrue(self::sandbox()->allows(new ReflectionMethod(TemplateVariable::class, 'label')));
    }

    public function testMethodWithAllowedPrefixIsAllowed(): void
    {
        self::assertTrue(self::sandbox()->allows(new ReflectionMethod(TemplateVariable::class, 'getTitle')));
    }

    public function testPrefixIsCaseSensitive(): void
    {
        self::assertFalse(self::sandbox()->allows(new ReflectionMethod(TemplateVariable::class, 'GetShouted')));
    }

    public function testOtherMethodIsNotAllowed(): void
    {
        self::assertFalse(self::sandbox()->allows(new ReflectionMethod(TemplateVariable::class, 'renew')));
    }

    public function testMethodWithTwigAllowedAttributeIsAllowed(): void
    {
        self::assertTrue(self::sandbox()->allows(new ReflectionMethod(TemplateVariable::class, 'renderAllowed')));
    }

    public function testAnyMethodOfClassOnAllowListIsAllowed(): void
    {
        self::assertTrue(self::sandbox()->allows(new ReflectionMethod(ClassOnAllowList::class, 'anything')));
    }

    public function testAnyMethodOfSubclassOfClassOnAllowListIsAllowed(): void
    {
        self::assertTrue(self::sandbox()->allows(new ReflectionMethod(SubclassOfClassOnAllowList::class, 'more')));
    }

    public function testCoreDefaultsApplyWithoutSiteSettings(): void
    {
        $policy = SandboxPolicy::fromSettings([]);
        self::assertTrue($policy->allows(new ReflectionMethod(TemplateVariable::class, 'label')));
    }

    /**
     * @param array<string, list<string>> $settingLists
     * @param MethodReference $methodTheSiteAllows
     * @param MethodReference $methodOnlyCoreAllows
     */
    #[DataProvider('siteSettings')]
    public function testSiteSettingAllowsWhatItLists(
        array $settingLists,
        array $methodTheSiteAllows,
        array $methodOnlyCoreAllows,
    ): void {
        self::assertTrue(
            SandboxPolicy::fromSettings($settingLists)->allows(new ReflectionMethod(...$methodTheSiteAllows)),
        );
    }

    /**
     * @param array<string, list<string>> $settingLists
     * @param MethodReference $methodTheSiteAllows
     * @param MethodReference $methodOnlyCoreAllows
     */
    #[DataProvider('siteSettings')]
    public function testSiteSettingReplacesCoreDefault(
        array $settingLists,
        array $methodTheSiteAllows,
        array $methodOnlyCoreAllows,
    ): void {
        self::assertFalse(
            SandboxPolicy::fromSettings($settingLists)->allows(new ReflectionMethod(...$methodOnlyCoreAllows)),
        );
    }

    /**
     * Settings, a method they allow, and a method only core's default allows.
     *
     * @return iterable<string, array{array<string, list<string>>, MethodReference, MethodReference}>
     */
    public static function siteSettings(): iterable
    {
        yield 'allowed classes' => [
            ['twig_sandbox_allowed_classes' => [ClassOnAllowList::class]],
            [ClassOnAllowList::class, 'anything'],
            [SubclassOfClassOnCoreAllowList::class, 'anyMethod'],
        ];
        yield 'allowed methods' => [
            ['twig_sandbox_allowed_methods' => ['renew']],
            [TemplateVariable::class, 'renew'],
            [TemplateVariable::class, 'label'],
        ];
        yield 'allowed prefixes' => [
            ['twig_sandbox_allowed_prefixes' => ['re']],
            [TemplateVariable::class, 'renew'],
            [TemplateVariable::class, 'getTitle'],
        ];
    }

    private static function sandbox(): SandboxPolicy
    {
        return new SandboxPolicy([ClassOnAllowList::class], ['label'], ['get']);
    }
}
