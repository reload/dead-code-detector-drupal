<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests\Contrib\Drush;

use Drupal\example\Drush\Factories\CommandsBuiltByCreate;
use Drupal\example\Drush\Factories\CommandsBuiltByCreateEarly;
use Drupal\example\Drush\Factories\CommandsBuiltByCreateInOtherCasing;
use Drupal\example\Drush\Factories\CommandsWithInstanceCreate;
use Drupal\example\Drush\Factories\CommandsWithOtherStaticMethod;
use Drupal\example\Drush\Factories\CommandsWithProtectedFactory;
use Drupal\example\Drush\Factories\FactoryOutsideDrushCommands;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Reload\DeadCodeDetectorDrupal\Contrib\Drush\DrushCommandClasses;

final class DrushCommandClassesTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function factories(): iterable
    {
        yield 'static create()' => [CommandsBuiltByCreate::class, 'create'];
        yield 'static createEarly()' => [CommandsBuiltByCreateEarly::class, 'createEarly'];
        yield 'create() in other casing' => [CommandsBuiltByCreateInOtherCasing::class, 'CREATE'];
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('factories')]
    public function testDrushBuildsCommandClassThroughFactory(string $className, string $methodName): void
    {
        self::assertTrue(DrushCommandClasses::isFactory(new ReflectionMethod($className, $methodName)));
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function nonFactories(): iterable
    {
        yield 'non-static create()' => [CommandsWithInstanceCreate::class, 'create'];
        yield 'protected create(), which Drush cannot call' => [CommandsWithProtectedFactory::class, 'create'];
        yield 'static method of another name' => [CommandsWithOtherStaticMethod::class, 'build'];
        yield 'static create() outside DrushCommands' => [FactoryOutsideDrushCommands::class, 'create'];
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('nonFactories')]
    public function testDrushDoesNotCallOtherMethodAsFactory(string $className, string $methodName): void
    {
        self::assertFalse(DrushCommandClasses::isFactory(new ReflectionMethod($className, $methodName)));
    }
}
