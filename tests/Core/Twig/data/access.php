<?php

declare(strict_types=1);

namespace Drupal\example\TwigAccess;

final class TemplateVariable
{
    public string $publicProperty = '';

    public static string $staticProperty = '';

    protected string $protectedProperty = '';

    public function publicMethod(): void
    {
    }

    public static function publicStaticMethod(): void
    {
    }

    protected function protectedMethod(): void
    {
    }
}
