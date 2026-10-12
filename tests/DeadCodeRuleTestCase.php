<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests;

use PHPStan\Analyser\Error;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function get_declared_classes;
use function implode;
use function is_subclass_of;
use function preg_match;
use function preg_quote;
use function sort;
use function str_contains;
use function str_starts_with;
use function substr;

/**
 * Runs dead-code-detector's DeadCodeRule as configured by its rules.neon and this
 * package's extension.neon over fixtures, and checks one fixture member per
 * test.
 *
 * Shipmonk's rule keeps state between analyses, so every test class with the
 * same config files shares one container and one analysis of all their
 * fixtures. Each class then keeps the messages about its own fixtures, which
 * live in Drupal\example\<Mechanism>, named after the test's directory. A
 * class alone with its config files keeps every message.
 *
 * @extends RuleTestCase<DeadCodeRule>
 */
abstract class DeadCodeRuleTestCase extends RuleTestCase
{
    /**
     * @var array<string, list<string>>
     *   Messages keyed by the config files of the container that produced them.
     */
    private static array $reportedMessagesByConfig = [];

    /**
     * The config files of the container, shared by every test with the
     * package's default parameters.
     *
     * @return non-empty-list<string>
     */
    protected static function fixtureConfigFiles(): array
    {
        return [__DIR__ . '/fixtures.neon'];
    }

    /**
     * The fixtures this class checks.
     *
     * @return non-empty-list<string>
     */
    abstract protected static function fixtureFiles(): array;

    /**
     * Members the providers under test must keep alive, keyed by case.
     *
     * @return iterable<string, array{string}>
     */
    abstract public static function usedMembers(): iterable;

    /**
     * Messages that must still be reported, keyed by case.
     *
     * @return iterable<string, array{string}>
     */
    abstract public static function deadMemberMessages(): iterable;

    /**
     * The namespace of this class's fixtures.
     */
    protected static function fixtureNamespace(): string
    {
        $classNameParts = explode('\\', static::class);

        return 'Drupal\example\\' . $classNameParts[count($classNameParts) - 2];
    }

    #[DataProvider('usedMembers')]
    final public function testMemberIsUsed(string $member): void
    {
        // Otherwise a misspelt member would pass.
        self::assertTrue(self::fixtureMemberExists($member), 'No fixture member ' . $member);

        $pattern = '/^(Unused|Property) ' . preg_quote($member, '/') . '( |$)/';
        foreach ($this->reportedMessages() as $message) {
            self::assertSame(0, preg_match($pattern, $message), $message);
        }
    }

    #[DataProvider('deadMemberMessages')]
    final public function testDeadMemberIsReported(string $message): void
    {
        self::assertContains($message, $this->reportedMessages());
    }

    final public function testNothingElseIsReported(): void
    {
        $expected = array_values(array_map(
            static fn (array $case): string => $case[0],
            [...static::deadMemberMessages()],
        ));

        self::assertEqualsCanonicalizing($expected, $this->reportedMessages());
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(DeadCodeRule::class);
    }

    /**
     * @return list<Collector<\PhpParser\Node, mixed>>
     */
    protected function getCollectors(): array
    {
        $collectors = [];
        foreach (self::getContainer()->getServicesByTag('phpstan.collector') as $collector) {
            if ($collector instanceof Collector) {
                $collectors[] = $collector;
            }
        }

        return $collectors;
    }

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            // As on a site: phpstan-drupal boots the Drupal core in vendor/.
            __DIR__ . '/../vendor/mglaman/phpstan-drupal/extension.neon',
            __DIR__ . '/../vendor/shipmonk/dead-code-detector/rules.neon',
            __DIR__ . '/../extension.neon',
            __DIR__ . '/rule-test.neon',
            ...static::fixtureConfigFiles(),
        ];
    }

    /**
     * Through the analysis's own reflection, which knows the core modules'
     * classes phpstan-drupal registers.
     */
    private static function fixtureMemberExists(string $member): bool
    {
        [$className, $memberName] = explode('::', $member, 2);
        $reflectionProvider = self::createReflectionProvider();
        if (!$reflectionProvider->hasClass($className)) {
            return false;
        }

        $class = $reflectionProvider->getClass($className)->getNativeReflection();

        return str_starts_with($memberName, '$')
            ? $class->hasProperty(substr($memberName, 1))
            : $class->hasMethod($memberName) || $class->hasConstant($memberName);
    }

    /**
     * @return list<string>
     */
    private function reportedMessages(): array
    {
        $configFiles = static::fixtureConfigFiles();
        $sharingClasses = self::classesConfiguredBy($configFiles);
        $messages = self::$reportedMessagesByConfig[implode("\n", $configFiles)] ??= array_map(
            static fn (Error $error): string => $error->getMessage(),
            $this->gatherAnalyserErrors(self::fixtureFilesOf($sharingClasses)),
        );
        if (count($sharingClasses) === 1) {
            return $messages;
        }

        // Messages name the member after a space: "Unused Ns\Class::member".
        $namespace = static::fixtureNamespace();

        return array_values(array_filter(
            $messages,
            static fn (string $message): bool => str_contains($message, ' ' . $namespace . '\\'),
        ));
    }

    /**
     * The test classes PHPUnit has loaded that share these config files,
     * this one included. Loaded, so running one test file analyses only its
     * own fixtures.
     *
     * @param non-empty-list<string> $configFiles
     * @return non-empty-list<class-string<self>>
     */
    private static function classesConfiguredBy(array $configFiles): array
    {
        $classes = [];
        foreach (get_declared_classes() as $class) {
            if (
                is_subclass_of($class, self::class)
                && !(new ReflectionClass($class))->isAbstract()
                && $class::fixtureConfigFiles() === $configFiles
            ) {
                $classes[] = $class;
            }
        }
        sort($classes);

        return $classes === [] ? [static::class] : $classes;
    }

    /**
     * @param non-empty-list<class-string<self>> $classes
     * @return list<string>
     */
    private static function fixtureFilesOf(array $classes): array
    {
        $files = [];
        foreach ($classes as $class) {
            $files = [...$files, ...$class::fixtureFiles()];
        }

        return array_values(array_unique($files));
    }
}
