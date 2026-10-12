<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\Twig\Sandbox {

    use Drupal\Core\Template\Attribute\TwigAllowed;

    final class TemplateVariable
    {
        public function label(): void
        {
        }

        public function getTitle(): void
        {
        }

        public function GetShouted(): void
        {
        }

        public function renew(): void
        {
        }

        #[TwigAllowed]
        public function renderAllowed(): void
        {
        }
    }

    class ClassOnAllowList
    {
        public function anything(): void
        {
        }
    }

    final class SubclassOfClassOnAllowList extends ClassOnAllowList
    {
        public function more(): void
        {
        }
    }

    final class SubclassOfClassOnCoreAllowList extends \Drupal\Core\Template\Attribute
    {
        public function anyMethod(): void
        {
        }
    }
}
