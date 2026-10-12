<?php

declare(strict_types=1);

namespace Drupal\example\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;

final class ExceptionSubscriberHandlingByName extends HttpExceptionSubscriberBase
{
    protected function getHandledFormats(): array
    {
        return ['html'];
    }

    public function on404(): void
    {
    }
}

final class HandlerNamedMethodOutsideSubscriber
{
    public function on404(): void
    {
    }
}
