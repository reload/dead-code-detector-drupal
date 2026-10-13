<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Twig;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use ReflectionMethod;
use ReflectionProperty;
use Reload\DeadCodeDetectorDrupal\Support\Usage\OriginFiles;
use ShipMonk\PHPStan\DeadCode\Graph\ClassPropertyUsage;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

use function basename;
use function sprintf;

/**
 * Members templates may access by name.
 *
 * Templates do not say what class a variable holds, so any public member with
 * an accessed name counts, but only methods the sandbox lets Twig call.
 *
 * @see \Drupal\Core\Template\TwigSandboxPolicy
 * @see https://www.drupal.org/docs/develop/theming-drupal/twig-in-drupal
 */
final class TwigUsageProvider extends ReflectionBasedMemberUsageProvider
{
    /**
     * Records the templates each usage comes from for the tests excluder.
     */
    public function __construct(
        private readonly TwigTemplates $templates,
        private readonly TwigSandbox $sandbox,
        private readonly OriginFiles $originFiles,
    ) {
    }

    /**
     * The base's usages, each with the templates it comes from, for the tests excluder.
     */
    public function getUsages(Node $node, Scope $scope): array
    {
        $usages = parent::getUsages($node, $scope);
        foreach ($usages as $usage) {
            $memberName = $usage->getMemberRef()->getMemberName() ?? '';
            $templatePaths = $usage instanceof ClassPropertyUsage
                ? $this->templates->accesses()->findTemplatesAccessing($memberName)
                : $this->templatesReaching($memberName);
            if ($templatePaths !== []) {
                $this->originFiles->record($usage, $templatePaths);
            }
        }

        return $usages;
    }

    /**
     * Used when a template accesses a name reaching the method and the sandbox
     * lets it call it.
     */
    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        if (!TwigAccess::callsMethod($method)) {
            return null;
        }

        // The sandbox walks the class's ancestry, so it is asked only about names templates access.
        $templatePaths = $this->templatesReaching($method->getName());

        return $templatePaths !== [] && $this->sandbox->policy()->allows($method) ? self::note($templatePaths) : null;
    }

    /**
     * Read when a template accesses the property by name; the sandbox does not
     * limit properties.
     */
    protected function shouldMarkPropertyAsRead(ReflectionProperty $property): ?VirtualUsageData
    {
        if (!TwigAccess::readsProperty($property)) {
            return null;
        }

        $templatePaths = $this->templates->accesses()->findTemplatesAccessing($property->getName());

        return $templatePaths !== [] ? self::note($templatePaths) : null;
    }

    /**
     * The templates accessing a name Twig resolves to the method: its own, or
     * without its get, is or has prefix.
     *
     * @return list<string>
     */
    private function templatesReaching(string $methodName): array
    {
        $templatePaths = [];
        foreach (TwigAccess::namesReaching($methodName) as $name) {
            $templatePaths = [...$templatePaths, ...$this->templates->accesses()->findTemplatesAccessing($name)];
        }

        return $templatePaths;
    }

    /**
     * The note, naming the first template accessing the name.
     *
     * @param non-empty-list<string> $templatePaths
     */
    private static function note(array $templatePaths): VirtualUsageData
    {
        return VirtualUsageData::withNote(sprintf('Name accessed in template %s', basename($templatePaths[0])));
    }
}
