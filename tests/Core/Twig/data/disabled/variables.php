<?php

declare(strict_types=1);

namespace Drupal\example\TwigDisabled;

final class TemplateVariable
{
    public function getShownByTemplate(): string
    {
        return '';
    }

    public function calledFromPhp(): void
    {
    }
}

function call_template_variable(TemplateVariable $variable): void
{
    $variable->calledFromPhp();
}
