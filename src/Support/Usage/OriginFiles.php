<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Usage;

use ShipMonk\PHPStan\DeadCode\Graph\ClassMemberUsage;
use WeakMap;

/**
 * Files that usages declared outside PHP come from: wiring YAML, templates.
 *
 * dead-code-detector's virtual usages carry no file, and its public way to
 * make a regular origin needs a PHP node; the constructor that could name any
 * file is internal. So providers record their files here, and the tests
 * excluder, which dead-code-detector hands the same usage objects, reads them
 * back.
 */
final class OriginFiles
{
    /**
     * Weak, so usages dead-code-detector drops take their files with them.
     *
     * @var WeakMap<ClassMemberUsage, non-empty-list<string>>
     */
    private readonly WeakMap $originFilesByUsage;

    /**
     * Starts with no files recorded.
     */
    public function __construct()
    {
        $this->originFilesByUsage = new WeakMap();
    }

    /**
     * Notes which files declare the usage, for the tests excluder.
     *
     * @param non-empty-list<string> $originFiles
     *   Each of them declares the usage.
     */
    public function record(ClassMemberUsage $usage, array $originFiles): void
    {
        $this->originFilesByUsage[$usage] = $originFiles;
    }

    /**
     * The files the usage came from; null for usages in PHP.
     *
     * @return non-empty-list<string>|null
     */
    public function find(ClassMemberUsage $usage): ?array
    {
        return $this->originFilesByUsage[$usage] ?? null;
    }
}
