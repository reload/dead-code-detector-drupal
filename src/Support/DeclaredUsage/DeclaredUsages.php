<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage;

use function array_values;
use function in_array;
use function ltrim;
use function strtolower;

/**
 * Methods that Drupal calls because its configuration (YAML) says so.
 */
final class DeclaredUsages
{
    /**
     * Private: none() and withMethod() build it.
     *
     * @param array<string, array<string, DeclaredMethodUsage>> $usagesByClass
     *   Lowercased class name => lowercased method name => usage.
     * @phpstan-pure
     */
    private function __construct(
        private readonly array $usagesByClass,
    ) {
    }

    /**
     * The start for a reader adding usages one at a time.
     *
     * @phpstan-pure
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * A method that several files declare keeps them all, so the tests
     * excluder can see whether any of them is outside tests.
     *
     * @phpstan-pure
     */
    public function withMethod(string $className, string $methodName, string $note, string $filePath): self
    {
        $classKey = self::classKey($className);
        $methodKey = strtolower($methodName);
        $usage = $this->usagesByClass[$classKey][$methodKey] ?? null;
        if ($usage !== null && in_array($filePath, $usage->wiringFiles, true)) {
            return $this;
        }

        $usagesByClass = $this->usagesByClass;
        $usagesByClass[$classKey][$methodKey] = $usage === null
            ? new DeclaredMethodUsage($methodName, $note, [$filePath])
            : new DeclaredMethodUsage($usage->methodName, $usage->note, [...$usage->wiringFiles, $filePath]);

        return new self($usagesByClass);
    }

    /**
     * Whether nothing is declared.
     */
    public function isEmpty(): bool
    {
        return $this->usagesByClass === [];
    }

    /**
     * The usages declared on the class itself, not on its parents.
     *
     * @return list<DeclaredMethodUsage>
     */
    public function usagesOf(string $className): array
    {
        return array_values($this->usagesByClass[self::classKey($className)] ?? []);
    }

    /**
     * PHP class names are case-insensitive, and YAML may name them with a
     * leading backslash.
     *
     * @phpstan-pure
     */
    private static function classKey(string $className): string
    {
        return strtolower(ltrim($className, '\\'));
    }
}
