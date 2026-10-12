<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Usage;

use ShipMonk\PHPStan\DeadCode\Graph\ClassMemberUsage;
use WeakMap;

/**
 * Files that usages declared outside PHP come from: wiring YAML, templates.
 *
 * Shipmonk's virtual usages carry no file, so providers record their files
 * here, and the tests excluder, which shipmonk hands the same usage objects,
 * reads them back.
 */
final class OriginFiles
{
    /**
     * @var WeakMap<ClassMemberUsage, non-empty-list<string>>
     */
    private readonly WeakMap $originFilesByUsage;

    public function __construct()
    {
        $this->originFilesByUsage = new WeakMap();
    }

    /**
     * @param non-empty-list<string> $originFiles
     *   Each of them declares the usage.
     */
    public function record(ClassMemberUsage $usage, array $originFiles): void
    {
        $this->originFilesByUsage[$usage] = $originFiles;
    }

    /**
     * @return non-empty-list<string>|null
     */
    public function find(ClassMemberUsage $usage): ?array
    {
        return $this->originFilesByUsage[$usage] ?? null;
    }
}
