<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Support\DeclaredUsage;

use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredMethodUsage;
use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;

use function array_map;
use function sort;

final class DeclaredMethods
{
    /**
     * @return list<string>
     *   Sorted, so assertions do not depend on declaration order.
     */
    public static function of(DeclaredUsages $usages, string $className): array
    {
        $methodNames = array_map(
            static fn (DeclaredMethodUsage $usage): string => $usage->methodName,
            $usages->usagesOf($className),
        );
        sort($methodNames);

        return $methodNames;
    }
}
