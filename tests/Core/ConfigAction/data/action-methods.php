<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Core\ConfigAction\ActionMethods {

    use Attribute;
    use Drupal\Core\Config\Action\Attribute\ActionMethod;

    #[Attribute(Attribute::TARGET_METHOD)]
    final class AttributeOtherThanActionMethod
    {
    }

    final class ClassWithTwoPublicActionMethods
    {
        #[ActionMethod]
        public function firstActionMethod(): void
        {
        }

        #[ActionMethod]
        public function secondActionMethod(): void
        {
        }
    }

    final class ClassWithProtectedActionMethod
    {
        #[ActionMethod]
        protected function protectedMethodWithActionMethodAttribute(): void
        {
        }
    }

    final class ClassWithPublicMethodWithoutAttributes
    {
        public function publicMethodWithoutAttributes(): void
        {
        }
    }

    final class ClassWithPublicMethodWithOtherAttribute
    {
        #[AttributeOtherThanActionMethod]
        public function publicMethodWithOtherAttribute(): void
        {
        }
    }

    class ParentWithInheritedActionMethod
    {
        #[ActionMethod]
        public function inheritedActionMethod(): void
        {
        }
    }

    final class ChildInheritingActionMethod extends ParentWithInheritedActionMethod
    {
    }

    class ParentWithOverriddenActionMethod
    {
        #[ActionMethod]
        public function actionMethodOverriddenWithoutAttribute(): void
        {
        }
    }

    final class ChildOverridingActionMethod extends ParentWithOverriddenActionMethod
    {
        public function actionMethodOverriddenWithoutAttribute(): void
        {
        }
    }
}
