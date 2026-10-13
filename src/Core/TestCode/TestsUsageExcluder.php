<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\TestCode;

use Reload\DeadCodeDetectorDrupal\Support\Usage\OriginFiles;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use ShipMonk\PHPStan\DeadCode\Excluder\MemberUsageExcluder;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMemberUsage;

/**
 * Usages from tests do not keep code outside tests alive.
 *
 * Sites declare no autoload-dev for Drupal's test namespaces, so dead-code-detector's
 * own tests excluder finds no test code.
 *
 * @see https://www.drupal.org/docs/automated-testing
 */
final class TestsUsageExcluder implements MemberUsageExcluder
{
    /**
     * With drupalDeadCode.testsExcluder.enabled off, it excludes nothing.
     */
    public function __construct(
        private readonly bool $enabled,
        private readonly TestPaths $testPaths,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly OriginFiles $originFiles,
    ) {
    }

    /**
     * Shown in dead-code-detector's message for members only tests use.
     */
    public function getIdentifier(): string
    {
        return 'drupalTests';
    }

    /**
     * Excludes a usage from test code of a member declared outside it.
     */
    public function shouldExclude(ClassMemberUsage $usage, Node $node, Scope $scope): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Usages that wiring declares come from its files, not from the class they name.
        if (!$this->allAreTestCode($this->originFiles->find($usage) ?? [$scope->getFile()])) {
            return false;
        }

        $className = $usage->getMemberRef()->getClassName();
        if ($className === null || !$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $declaringFile = $this->reflectionProvider->getClass($className)->getFileName();

        return $declaringFile !== null && !$this->testPaths->contains($declaringFile);
    }

    /**
     * Whether every file the usage comes from is test code.
     *
     * @param non-empty-list<string> $files
     */
    private function allAreTestCode(array $files): bool
    {
        foreach ($files as $file) {
            if (!$this->testPaths->contains($file)) {
                return false;
            }
        }

        return true;
    }
}
