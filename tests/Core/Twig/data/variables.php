<?php

declare(strict_types=1);

namespace Drupal\example;

function create_template_variable(): TemplateVariable
{
    return new TemplateVariable('read by template');
}

final class TemplateVariable
{
    protected string $protectedPropertyAccess = '';

    public function __construct(
        public string $propertyRead,
    ) {
    }

    public function label(): string
    {
        return '';
    }

    public function isPublished(): bool
    {
        return true;
    }

    public function notAllowedBySandbox(): void
    {
    }

    protected function getProtectedAccess(): string
    {
        return '';
    }
}
