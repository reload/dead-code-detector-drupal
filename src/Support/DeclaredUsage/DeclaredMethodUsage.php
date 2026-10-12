<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage;

/**
 * A method that wiring files say Drupal calls.
 */
final class DeclaredMethodUsage
{
    /**
     * @param non-empty-list<string> $wiringFiles
     *   Each of them declares the call.
     * @phpstan-pure
     */
    public function __construct(
        public readonly string $methodName,
        public readonly string $note,
        public readonly array $wiringFiles,
    ) {
    }
}
