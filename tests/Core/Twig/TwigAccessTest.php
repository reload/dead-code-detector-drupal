<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Twig;

use Drupal\example\TwigAccess\TemplateVariable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Reload\DeadCodeDetectorDrupal\Core\Twig\TwigAccess;

final class TwigAccessTest extends TestCase
{
    public function testTwigCallsPublicMethod(): void
    {
        self::assertTrue(TwigAccess::callsMethod(new ReflectionMethod(TemplateVariable::class, 'publicMethod')));
    }

    public function testTwigCallsPublicStaticMethodThroughTheObject(): void
    {
        self::assertTrue(TwigAccess::callsMethod(new ReflectionMethod(TemplateVariable::class, 'publicStaticMethod')));
    }

    public function testTwigDoesNotCallProtectedMethod(): void
    {
        self::assertFalse(TwigAccess::callsMethod(new ReflectionMethod(TemplateVariable::class, 'protectedMethod')));
    }

    public function testTwigReadsPublicProperty(): void
    {
        self::assertTrue(TwigAccess::readsProperty(new ReflectionProperty(TemplateVariable::class, 'publicProperty')));
    }

    public function testTwigDoesNotReadStaticProperty(): void
    {
        self::assertFalse(TwigAccess::readsProperty(new ReflectionProperty(TemplateVariable::class, 'staticProperty')));
    }

    public function testTwigDoesNotReadProtectedProperty(): void
    {
        self::assertFalse(
            TwigAccess::readsProperty(new ReflectionProperty(TemplateVariable::class, 'protectedProperty')),
        );
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function namesReachingMethods(): iterable
    {
        yield 'getter, by its name and by the name after "get"' => ['getUrl', ['geturl', 'url']];
        yield 'isser, by the name after "is"' => ['isPublished', ['ispublished', 'published']];
        yield 'hasser, by the name after "has"' => ['hasAttachments', ['hasattachments', 'attachments']];
        yield 'getter whose name goes on in lower case' => ['getaway', ['getaway', 'away']];
        yield 'getter whose prefix is capitalised' => ['GetUrl', ['geturl', 'url']];
        yield 'other method, by its name only' => ['label', ['label']];
        yield 'a bare prefix, by its name only' => ['get', ['get']];
    }

    /**
     * @param list<string> $expectedNames
     */
    #[DataProvider('namesReachingMethods')]
    public function testNamesReachingMethod(string $methodName, array $expectedNames): void
    {
        self::assertSame($expectedNames, TwigAccess::namesReaching($methodName));
    }
}
