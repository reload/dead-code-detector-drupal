<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Tests;

use PHPStan\Analyser\Error;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function implode;
use function preg_match;
use function preg_quote;
use function str_contains;
use function str_starts_with;
use function substr;

/**
 * Runs shipmonk's DeadCodeRule as configured by its rules.neon and this
 * package's extension.neon over fixtures, once per analysis, and checks one
 * fixture member per test.
 *
 * Shipmonk's rule keeps state between analyses, so classes with the same
 * config and analysed files share one analysis, and each keeps the messages
 * of its own fixture namespace. Otherwise a class gets a container of its own.
 *
 * @extends RuleTestCase<DeadCodeRule>
 */
abstract class DeadCodeRuleTestCase extends RuleTestCase
{
    /**
     * @var array<string, list<string>>
     *   Messages keyed by the config and analysed files that produced them.
     */
    private static array $reportedMessagesByAnalysis = [];

    /**
     * At least one file of the test's own, or of its group's.
     *
     * @return non-empty-list<string>
     */
    abstract protected static function fixtureConfigFiles(): array;

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
     * The files analysed together: a group's base lists every member's.
     *
     * @return non-empty-list<string>
     */
    protected static function analysedFixtureFiles(): array
    {
        return static::fixtureFiles();
    }

    /**
     * The namespace of this class's fixtures, when others share the analysis.
     */
    protected static function fixtureNamespace(): ?string
    {
        return null;
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
        $analysisKey = implode("\n", [...static::fixtureConfigFiles(), ...static::analysedFixtureFiles()]);
        $messages = self::$reportedMessagesByAnalysis[$analysisKey] ??= array_map(
            static fn (Error $error): string => $error->getMessage(),
            $this->gatherAnalyserErrors(static::analysedFixtureFiles()),
        );

        $namespace = static::fixtureNamespace();
        if ($namespace === null) {
            return $messages;
        }

        // Messages name the member after a space: "Unused Ns\Class::member".
        return array_values(array_filter(
            $messages,
            static fn (string $message): bool => str_contains($message, ' ' . $namespace . '\\'),
        ));
    }
}
