<?php

declare(strict_types=1);

namespace Drupal\example\Controller {
    use Symfony\Component\Routing\Attribute\Route;

    final class ControllerRoutedByMethod
    {
        public function __construct()
        {
        }

        #[Route('/routed-by-method', name: 'example.routed_by_method')]
        public function page(): void
        {
        }
    }

    #[Route('/routed-by-class', name: 'example.routed_by_class')]
    final class InvokableControllerRoutedByClass
    {
        public function __construct()
        {
        }

        public function __invoke(): void
        {
        }
    }

    abstract class BaseOfRoutedController
    {
        public function __construct()
        {
        }
    }

    final class RoutedControllerInheritingConstructor extends BaseOfRoutedController
    {
        #[Route('/inheriting-constructor', name: 'example.inheriting_constructor')]
        public function page(): void
        {
        }
    }

    abstract class BaseWithRoutedMethod
    {
        #[Route('/inherited-route', name: 'example.inherited_route')]
        public function page(): void
        {
        }
    }

    final class ControllerInheritingRoutedMethod extends BaseWithRoutedMethod
    {
        public function __construct()
        {
        }
    }

    final class RoutedControllerInheritingBuiltInMethods extends \ArrayIterator
    {
        public function __construct()
        {
            parent::__construct([]);
        }

        #[Route('/inheriting-built-in-methods', name: 'example.inheriting_built_in_methods')]
        public function page(): void
        {
        }
    }

    abstract class AbstractRoutedController
    {
        public function __construct()
        {
        }

        #[Route('/abstract-routed', name: 'example.abstract_routed')]
        public function page(): void
        {
        }
    }

    #[Route('/class-routed-without-invoke', name: 'example.class_routed_without_invoke')]
    final class ClassRoutedControllerWithoutInvoke
    {
        public function __construct()
        {
        }
    }

    final class ControllerWithoutRoutes
    {
        public function __construct()
        {
        }
    }
}
