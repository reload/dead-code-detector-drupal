<?php

declare(strict_types=1);

namespace Drupal\example;

final class ProductionCode
{
    public static function calledOnlyFromTests(): void
    {
    }

    public static function calledFromProductionCode(): void
    {
    }

    public static function calledByNothing(): void
    {
    }

    /**
     * @return array<string, string>
     */
    public function routedByModule(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function routedOnlyByTestModule(): array
    {
        return [];
    }

    public function getShownOnlyByTestTheme(): string
    {
        return '';
    }

    public function getShownByModuleAndTestTheme(): string
    {
        return '';
    }
}

function run_production_code(): void
{
    ProductionCode::calledFromProductionCode();
}
