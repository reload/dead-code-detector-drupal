<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\TestCode;

use Symfony\Component\Filesystem\Path;

use function array_map;
use function basename;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Test code: anything in a "tests" directory below an analysed path, where
 * Drupal extensions keep their tests.
 *
 * @see https://www.drupal.org/docs/automated-testing
 */
final class TestPaths
{
    /**
     * Canonical, to compare with canonical file paths.
     *
     * @var list<string>
     */
    private readonly array $analysedPaths;

    /**
     * Canonicalises the analysed paths once, rather than for every file it checks.
     *
     * @param list<string> $analysedPaths
     */
    public function __construct(array $analysedPaths)
    {
        $this->analysedPaths = array_map(Path::canonicalize(...), $analysedPaths);
    }

    /**
     * Whether the file is test code.
     */
    public function contains(string $filePath): bool
    {
        $filePath = Path::canonicalize($filePath);
        foreach ($this->analysedPaths as $analysedPath) {
            // From the analysed path's own name down: a checkout under /builds/tests/ is not all
            // tests, but an analysed tests/ directory is.
            if (str_starts_with($filePath, $analysedPath . '/')) {
                $relativePath = '/' . basename($analysedPath) . substr($filePath, strlen($analysedPath));
                if (str_contains($relativePath, '/tests/')) {
                    return true;
                }
            }
        }

        return false;
    }
}
