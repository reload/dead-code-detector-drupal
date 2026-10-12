<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Twig;

use PHPUnit\Framework\TestCase;
use Reload\DeadCodeDetectorDrupal\Core\Twig\TwigTemplates;
use Reload\DeadCodeDetectorDrupal\Core\Twig\TemplateAccesses;

final class TwigTemplatesTest extends TestCase
{
    public function testNamesAccessedInPrintAndTagBlocksAreFound(): void
    {
        $templates = self::parse('{{ node.label }}{% if node.isPublished() %}{% endif %}');

        self::assertSame(['label', 'ispublished'], $templates->accessedNames());
    }

    public function testNamesAccessedOnCallsAndSubscriptsAreFound(): void
    {
        $templates = self::parse("{{ node.owner().name }}{{ items[0].title }}");

        self::assertSame(['owner', 'name', 'title'], $templates->accessedNames());
    }

    public function testEveryAccessOfAChainIsFound(): void
    {
        $templates = self::parse('{{ node.owner.name }}');

        self::assertSame(['owner', 'name'], $templates->accessedNames());
    }

    public function testAccessAfterANumericSegmentIsFound(): void
    {
        $templates = self::parse('{{ items.0.getTitle() }}');

        self::assertSame(['gettitle'], $templates->accessedNames());
    }

    public function testAccessInAMultiLineTagIsFound(): void
    {
        $templates = self::parse("{%\n  if node.isPublished()\n%}");

        self::assertSame(['ispublished'], $templates->accessedNames());
    }

    public function testAccessWithWhitespaceAroundTheDotIsFound(): void
    {
        $templates = self::parse("{{ node . label }}{{ node\n  .getOwner() }}");

        self::assertSame(['label', 'getowner'], $templates->accessedNames());
    }

    public function testDottedNamesInMarkupAreNotAccesses(): void
    {
        $templates = self::parse('<div class="node.markupOnly"></div>');

        self::assertSame([], $templates->accessedNames());
    }

    public function testDecimalNumbersAreNotAccesses(): void
    {
        $templates = self::parse('{{ 1.5 }}');

        self::assertSame([], $templates->accessedNames());
    }

    public function testNamesInStringLiteralsAreNotAccesses(): void
    {
        $templates = self::parse("{{ 'node.inString' }}");

        self::assertSame([], $templates->accessedNames());
    }

    public function testNullSafeAccessIsFound(): void
    {
        $templates = self::parse('{{ node?.label }}');

        self::assertSame(['label'], $templates->accessedNames());
    }

    public function testAccessInsideDrupalsOwnTagsIsFound(): void
    {
        $templates = self::parse('{% trans %}{{ node.label }}{% endtrans %}');

        self::assertSame(['label'], $templates->accessedNames());
    }

    public function testTemplateTwigCannotLexHasNoAccesses(): void
    {
        $templates = self::parse('{{ node.label ` }}');

        self::assertSame([], $templates->accessedNames());
    }

    public function testAccessIsFoundRegardlessOfCase(): void
    {
        $templates = self::parse('{{ node.GetUrl() }}');

        self::assertSame(['/site/example.html.twig'], $templates->findTemplatesAccessing('getUrl'));
    }

    public function testEachTemplateAccessingANameIsFoundOnce(): void
    {
        $templates = TwigTemplates::parse([
            '/site/first.html.twig' => '{{ node.label }}',
            '/site/second.html.twig' => '{{ node.label }}{{ node.label }}',
        ]);

        self::assertSame(
            ['/site/first.html.twig', '/site/second.html.twig'],
            $templates->findTemplatesAccessing('label'),
        );
    }

    private static function parse(string $source): TemplateAccesses
    {
        return TwigTemplates::parse(['/site/example.html.twig' => $source]);
    }
}
