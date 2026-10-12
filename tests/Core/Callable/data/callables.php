<?php

declare(strict_types=1);

namespace Drupal\example;

function build_form(): array
{
    return (new FormWithStringCallbacks())->buildForm();
}

function named_class_concatenation(): string
{
    return CallbackTarget::class . '::calledByNamedClassConcatenation';
}

final class FormWithStringCallbacks
{
    public function buildForm(): array
    {
        return [
            '#submit' => ['::calledByFormObjectString'],
            '#validate' => ['\Drupal\example\CallbackTarget::calledByClassString'],
            '#pre_render' => ['example.callable_service:calledByServiceString'],
            '#after_build' => [static::class . ':calledByStaticClassConcatenation'],
            '#process' => ['undeclared.service:namedOnlyThroughUndeclaredService'],
            '#element_validate' => ['Drupal\example\InstantiatedCallbackHolder:calledBySingleColonLiteral'],
            '#post_render' => ['\Drupal\example\CallbackTarget::noSuchMethod'],
        ];
    }

    public function calledByFormObjectString(): void
    {
    }

    public function calledByStaticClassConcatenation(): void
    {
    }

    public function namedOnlyThroughUndeclaredService(): void
    {
    }
}

final class CallbackTarget
{
    public function __construct()
    {
    }

    public static function calledByClassString(): void
    {
    }

    public static function calledByNamedClassConcatenation(): void
    {
    }
}

final class CallableService
{
    public function calledByServiceString(): void
    {
    }
}

final class CallbackOnlyNamedInDeadCode
{
    public function deadFormBuilder(): array
    {
        return ['#submit' => ['::submitNamedOnlyInDeadCode']];
    }

    public function submitNamedOnlyInDeadCode(): void
    {
    }
}

final class InstantiatedCallbackHolder
{
    public function __construct()
    {
    }

    public function calledBySingleColonLiteral(): void
    {
    }
}
