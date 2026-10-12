<?php

declare(strict_types=1);

namespace Drupal\Tests\example;

use Drupal\example\ProductionCode;

function test_production_code(): void
{
    ProductionCode::calledOnlyFromTests();
    ProductionCode::calledFromProductionCode();
    TestHelper::helperCalledFromTests();
}

final class TestHelper
{
    public static function helperCalledFromTests(): void
    {
    }
}
