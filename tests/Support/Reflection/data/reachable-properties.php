<?php

declare(strict_types=1);

namespace Drupal\example\ReachableProperties;

final class ClassWithPropertyOfEachKind
{
    public string $publicProperty = '';

    protected string $protectedProperty = '';

    private string $privateProperty = '';

    public static string $staticProperty = '';
}
