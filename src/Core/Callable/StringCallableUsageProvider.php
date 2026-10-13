<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Callable;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use Reload\DeadCodeDetectorDrupal\Core\Services\ServiceDefinitions;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;

use function array_map;
use function in_array;
use function ltrim;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

/**
 * Callables Drupal accepts as strings, e.g. in render arrays and routes set
 * up in PHP: "::method" on the form object, "Class::method", "Class:method",
 * "service:method", and the same built as Foo::class . '::method'.
 *
 * A string is split as Drupal splits it; the service wiring and PHPStan's
 * reflection then say whether the pieces exist, so nothing else is guessed.
 * Usages originate where the string is, so a callback only kept alive by
 * dead code is dead too.
 *
 * @see \Drupal\Core\Utility\CallableResolver::getCallableFromDefinition()
 * @see \Drupal\Core\Form\FormState::prepareCallback()
 * @see https://www.drupal.org/node/1734540
 */
final class StringCallableUsageProvider implements MemberUsageProvider
{
    public function __construct(
        private readonly ServiceDefinitions $services,
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getUsages(Node $node, Scope $scope): array
    {
        $callees = match (true) {
            $node instanceof String_ => $this->resolveString($node->value, $scope),
            $node instanceof Concat => $this->resolveConcatenation($node, $scope),
            default => [],
        };
        $origin = UsageOrigin::createRegular($node, $scope);

        return array_map(
            static fn (ClassMethodRef $callee): ClassMethodUsage => new ClassMethodUsage($origin, $callee),
            $callees,
        );
    }

    /**
     * @return list<ClassMethodRef<string, string>>
     */
    private function resolveString(string $callable, Scope $scope): array
    {
        // Form API resolves "::method" on the form object.
        if (str_starts_with($callable, '::')) {
            return self::onScopeClass(substr($callable, 2), $scope);
        }

        $split = DrupalCallables::split($callable);
        if ($split === null) {
            return [];
        }

        [$target, $methodName] = $split;
        $serviceClassName = $this->services->wiring()->classOf($target);

        return $serviceClassName !== null
            // The container constructs services; see ServiceDefinitions.
            ? $this->onClass($serviceClassName, $methodName, constructed: false)
            : $this->onClass(ltrim($target, '\\'), $methodName, constructed: true);
    }

    /**
     * Foo::class . '::method', or static::class . ':method' for classes that
     * are services under their class name, such as hook classes.
     *
     * @return list<ClassMethodRef<string, string>>
     */
    private function resolveConcatenation(Concat $concatenation, Scope $scope): array
    {
        $class = $concatenation->left;
        $method = $concatenation->right;
        if (
            !$class instanceof ClassConstFetch
            || !$class->class instanceof Name
            || !$class->name instanceof Identifier
            || $class->name->toLowerString() !== 'class'
            || !$method instanceof String_
        ) {
            return [];
        }

        $methodName = ltrim($method->value, ':');
        if (!in_array(strlen($method->value) - strlen($methodName), [1, 2], true)) {
            return [];
        }

        return in_array(strtolower($class->class->toString()), ['self', 'static'], true)
            ? self::onScopeClass($methodName, $scope)
            : $this->onClass($scope->resolveName($class->class), $methodName, constructed: true);
    }

    /**
     * The method, if the class declares or inherits it, and the constructor
     * when the class resolver instantiates the class to call a non-static
     * method.
     *
     * @return list<ClassMethodRef<string, string>>
     */
    private function onClass(string $className, string $methodName, bool $constructed): array
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        $class = $this->reflectionProvider->getClass($className);
        if (!$class->hasNativeMethod($methodName)) {
            return [];
        }

        $callees = [new ClassMethodRef($class->getName(), $methodName, possibleDescendant: false)];
        if ($constructed && !$class->getNativeMethod($methodName)->isStatic()) {
            $callees[] = new ClassMethodRef($class->getName(), '__construct', possibleDescendant: false);
        }

        return $callees;
    }

    /**
     * On the class being analysed, or a subclass: a form's base class may
     * name handlers its subclasses declare.
     *
     * @return list<ClassMethodRef<string, string>>
     */
    private static function onScopeClass(string $methodName, Scope $scope): array
    {
        $class = $scope->getClassReflection();

        return $class !== null && DrupalCallables::isMethodName($methodName)
            ? [new ClassMethodRef($class->getName(), $methodName, possibleDescendant: true)]
            : [];
    }
}
