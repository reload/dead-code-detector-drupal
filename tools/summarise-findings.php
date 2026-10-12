<?php

/**
 * Summarises dead-code findings in PHPStan JSON output.
 *
 * Usage: php tools/summarise-findings.php RUN.json [EARLIER_RUN.json]
 * With two runs, also shows what the later run no longer reports.
 */

declare(strict_types=1);

const GENERATED_CODE_PATTERN = '#/src/(GraphQL/(Operations|Types)|ProxyClass)/#';

/**
 * @return list<array{file: string, line: int, identifier: string, message: string}>
 */
function readFindings(string $path): array
{
    $output = (string) file_get_contents($path);
    // Container entrypoints may print a line before PHPStan's JSON.
    $report = json_decode(substr($output, (int) strpos($output, '{')), true, flags: JSON_THROW_ON_ERROR);

    return parseFindings($report);
}

/**
 * @param array<mixed> $report
 * @return list<array{file: string, line: int, identifier: string, message: string}>
 */
function parseFindings(array $report): array
{
    $findings = [];
    foreach ($report['files'] ?? [] as $file => $fileReport) {
        foreach ($fileReport['messages'] ?? [] as $message) {
            $identifier = $message['identifier'] ?? '';
            if (str_starts_with($identifier, 'shipmonk.')) {
                $findings[] = [
                    'file' => $file,
                    'line' => (int) ($message['line'] ?? 0),
                    'identifier' => $identifier,
                    'message' => $message['message'],
                ];
            }
        }
    }

    return $findings;
}

function locationOf(string $file): string
{
    return match (true) {
        preg_match(GENERATED_CODE_PATTERN, $file) === 1 => 'generated',
        str_contains($file, '/tests/') => 'tests',
        preg_match('#\.(module|inc|install|theme|profile)$#', $file) === 1 => 'procedural',
        default => 'src',
    };
}

function extensionOf(string $file): string
{
    return preg_match('#/(modules/custom|themes/custom|profiles)/([^/]+)#', $file, $match) === 1 ? $match[2] : '?';
}

/**
 * @param array<string, int> $counts
 */
function printCounts(string $title, array $counts, int $limit = PHP_INT_MAX): void
{
    arsort($counts);
    echo "\n{$title}\n";
    foreach (array_slice($counts, 0, $limit, true) as $key => $count) {
        printf("  %5d  %s\n", $count, $key);
    }
}

/**
 * @param list<array{file: string, line: int, identifier: string, message: string}> $findings
 */
function summarise(array $findings): void
{
    $byIdentifier = $byLocation = $byExtension = [];
    foreach ($findings as $finding) {
        $byIdentifier[$finding['identifier']] = ($byIdentifier[$finding['identifier']] ?? 0) + 1;
        $location = locationOf($finding['file']);
        $byLocation[$location] = ($byLocation[$location] ?? 0) + 1;
        $extension = extensionOf($finding['file']);
        $byExtension[$extension] = ($byExtension[$extension] ?? 0) + 1;
    }

    printf("%d dead-code findings\n", count($findings));
    printCounts('By identifier', $byIdentifier);
    printCounts('By location', $byLocation);
    printCounts('By extension (top 15)', $byExtension, 15);
}

$findings = readFindings($argv[1]);
summarise($findings);

if (isset($argv[2])) {
    $key = static fn (array $finding): string => $finding['file'] . ':' . $finding['line'] . ':' . $finding['message'];
    $current = array_flip(array_map($key, $findings));
    $gone = array_values(array_filter(readFindings($argv[2]), static fn (array $finding): bool => !isset($current[$key($finding)])));

    echo "\nNo longer reported compared to {$argv[2]}:\n";
    summarise($gone);
}
