<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Support\Yaml;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use function is_array;

final class YamlDocument
{
    /**
     * Invalid YAML yields nothing: Drupal would fail on it before any dead
     * code matters.
     *
     * @return array<mixed>
     * @phpstan-pure
     */
    public static function parseMapping(string $yaml): array
    {
        try {
            // @phpstan-ignore possiblyImpure.methodCall (Symfony's parser only reads its input)
            $data = Yaml::parse($yaml, Yaml::PARSE_CUSTOM_TAGS);
        } catch (ParseException) {
            return [];
        }

        return is_array($data) ? $data : [];
    }
}
