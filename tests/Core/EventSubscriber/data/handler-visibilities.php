<?php

declare(strict_types=1);

namespace Drupal\example\EventSubscriber\Handlers;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;

final class ExceptionSubscriberWithHandlerVisibilities extends HttpExceptionSubscriberBase
{
    protected function getHandledFormats(): array
    {
        return ['html'];
    }

    public function on404(): void
    {
    }

    protected function on403(): void
    {
    }

    private function on410(): void
    {
    }
}

final class HandlerNamedMethodOnPlainClass
{
    public function on404(): void
    {
    }
}
