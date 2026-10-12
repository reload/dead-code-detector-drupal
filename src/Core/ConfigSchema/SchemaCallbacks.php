<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\ConfigSchema;

use Reload\DeadCodeDetectorDrupal\Support\DeclaredUsage\DeclaredUsages;
use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFiles;
use Reload\DeadCodeDetectorDrupal\Support\Yaml\YamlDocument;

use function basename;
use function explode;
use function is_array;
use function is_string;
use function ltrim;
use function str_contains;

/**
 * Callback constraints in config schema: methods config validation calls.
 *
 * @see \Symfony\Component\Validator\Constraints\Callback
 * @see https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata
 */
final class SchemaCallbacks
{
    private ?DeclaredUsages $declaredUsages = null;

    public function __construct(
        private readonly DrupalFiles $drupalFiles,
    ) {
    }

    /**
     * Read on first use.
     */
    public function declaredUsages(): DeclaredUsages
    {
        return $this->declaredUsages ??= self::parse($this->drupalFiles->readWithSuffix(DrupalFiles::CONFIG_SCHEMA));
    }

    /**
     * @param array<string, string> $yamlByFilePath
     * @phpstan-pure
     */
    public static function parse(array $yamlByFilePath): DeclaredUsages
    {
        $usages = DeclaredUsages::none();
        foreach ($yamlByFilePath as $filePath => $yaml) {
            $note = 'Callback constraint in ' . basename($filePath);
            foreach (self::callbacks(YamlDocument::parseMapping($yaml)) as [$className, $methodName]) {
                $usages = $usages->withMethod($className, $methodName, $note, $filePath);
            }
        }

        return $usages;
    }

    /**
     * @param array<mixed> $schema
     * @return list<array{string, string}>
     * @phpstan-pure
     */
    private static function callbacks(array $schema): array
    {
        $callbacks = [];
        foreach ($schema as $key => $value) {
            // "Callback: {callback: ...}" names the option; "Callback: ..." uses it as the default option.
            $isCallback = $key === 'callback'
                || ($key === 'Callback' && !(is_array($value) && isset($value['callback'])));
            if ($isCallback) {
                $callback = match (true) {
                    is_array($value) && is_string($value[0] ?? null) && is_string($value[1] ?? null)
                        => [ltrim($value[0], '\\'), $value[1]],
                    is_string($value) && str_contains($value, '::') => explode('::', ltrim($value, '\\'), 2),
                    default => null,
                };
                if ($callback !== null && str_contains($callback[0], '\\')) {
                    $callbacks[] = [$callback[0], $callback[1]];
                }
            } elseif (is_array($value)) {
                $callbacks = [...$callbacks, ...self::callbacks($value)];
            }
        }

        return $callbacks;
    }
}
