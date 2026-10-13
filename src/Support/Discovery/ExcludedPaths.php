<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Discovery;

use Symfony\Component\Filesystem\Path;

use function fnmatch;
use function strpbrk;

/**
 * PHPStan's excludePaths, matched the way PHPStan's own FileExcluder does,
 * which is internal: a pattern with wildcards, or a file or directory.
 */
final class ExcludedPaths
{
    /**
     * Without paths, it excludes nothing.
     *
     * @param list<string> $excludedPaths
     *   Absolute paths or fnmatch patterns.
     * @phpstan-pure
     */
    public function __construct(
        private readonly array $excludedPaths = [],
    ) {
    }

    /**
     * Both of PHPStan's lists: either keeps a file from being analysed.
     *
     * @param array{analyse?: list<string>, analyseAndScan?: list<string>} $excludePathsParameter
     *   PHPStan's %excludePaths%, in the shape its parameter schema enforces.
     * @phpstan-pure
     */
    public static function fromParameter(array $excludePathsParameter): self
    {
        return new self([
            ...$excludePathsParameter['analyse'] ?? [],
            ...$excludePathsParameter['analyseAndScan'] ?? [],
        ]);
    }

    /**
     * Whether PHPStan excludes the file.
     */
    public function contains(string $filePath): bool
    {
        $filePath = Path::canonicalize($filePath);
        foreach ($this->excludedPaths as $excludedPath) {
            $isPattern = strpbrk($excludedPath, '*?[') !== false;
            if ($isPattern ? fnmatch($excludedPath, $filePath) : Path::isBasePath($excludedPath, $filePath)) {
                return true;
            }
        }

        return false;
    }
}
