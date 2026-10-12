<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use Reload\DeadCodeDetectorDrupal\Support\Usage\OriginFiles;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

/**
 * Marks the methods a mechanism's wiring declares as used.
 *
 * Usages are emitted while analysing the class they name, as references
 * through that class, so shipmonk resolves methods declared in its ancestors.
 */
abstract class DeclaredUsageProvider implements MemberUsageProvider
{
    public function __construct(
        private readonly OriginFiles $originFiles,
    ) {
    }

    /**
     * The methods the mechanism's wiring declares, from a reader that reads once.
     */
    abstract protected function declaredUsages(): DeclaredUsages;

    public function getUsages(Node $node, Scope $scope): array
    {
        // @phpstan-ignore phpstanApi.instanceofAssumption
        if (!$node instanceof InClassNode) {
            return [];
        }

        $className = $node->getClassReflection()->getName();
        $usages = [];
        foreach ($this->declaredUsages()->usagesOf($className) as $declaredUsage) {
            $usage = new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote($declaredUsage->note)),
                new ClassMethodRef($className, $declaredUsage->methodName, possibleDescendant: false),
            );
            $this->originFiles->record($usage, $declaredUsage->wiringFiles);
            $usages[] = $usage;
        }

        return $usages;
    }
}
