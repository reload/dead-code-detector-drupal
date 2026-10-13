<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Services;

use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;

/**
 * What *.services.yml wires: each service's class, and the methods the
 * container calls.
 */
final class ServiceWiring
{
    /**
     * Built by ServiceDefinitions::parse().
     *
     * @param array<string, string> $classByServiceId
     * @phpstan-pure
     */
    public function __construct(
        private readonly array $classByServiceId,
        private readonly DeclaredUsages $declaredUsages,
    ) {
    }

    /**
     * The class of the service, or of the one an alias names.
     *
     * @phpstan-pure
     */
    public function classOf(string $serviceId): ?string
    {
        return $this->classByServiceId[$serviceId] ?? null;
    }

    /**
     * The methods the container calls.
     */
    public function declaredUsages(): DeclaredUsages
    {
        return $this->declaredUsages;
    }
}
