<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Twig;

use function array_keys;
use function strtolower;

/**
 * The names templates access, and which templates access each.
 */
final class TemplateAccesses
{
    /**
     * Built by TwigTemplates::parse().
     *
     * @param array<string, non-empty-list<string>> $templatePathsByName
     *   Lowercased accessed name => templates accessing it.
     * @phpstan-pure
     */
    public function __construct(
        private readonly array $templatePathsByName,
    ) {
    }

    /**
     * Templates accessing the name.
     *
     * @return list<string>
     */
    public function findTemplatesAccessing(string $name): array
    {
        return $this->templatePathsByName[strtolower($name)] ?? [];
    }

    /**
     * Every name accessed, lowercased.
     *
     * @return list<string>
     */
    public function accessedNames(): array
    {
        return array_keys($this->templatePathsByName);
    }
}
