<?php

declare(strict_types=1);

namespace Reload\DeadCodeDetectorDrupal\Core\Twig;

use Reload\DeadCodeDetectorDrupal\Support\Discovery\DrupalFiles;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Source;
use Twig\Token;

use function array_map;
use function array_values;
use function in_array;
use function is_string;
use function strtolower;

/**
 * Names templates access on variables: "node.label", "item.getUrl()".
 *
 * @see https://www.drupal.org/docs/develop/theming-drupal/twig-in-drupal
 */
final class TwigTemplates
{
    private ?TemplateAccesses $accesses = null;

    public function __construct(
        private readonly DrupalFiles $drupalFiles,
    ) {
    }

    /**
     * Read on first use; with Twig matching off, DrupalFiles finds no templates.
     */
    public function accesses(): TemplateAccesses
    {
        return $this->accesses ??= self::parse($this->drupalFiles->readWithSuffix(DrupalFiles::TWIG_TEMPLATE));
    }

    /**
     * @param array<string, string> $sourceByTemplatePath
     * @phpstan-pure
     */
    public static function parse(array $sourceByTemplatePath): TemplateAccesses
    {
        // Lexing needs no extensions, so templates using Drupal's own tags lex too.
        $environment = new Environment(new ArrayLoader());
        $templatePathsByName = [];
        foreach ($sourceByTemplatePath as $templatePath => $source) {
            foreach (self::accessedNamesIn($environment, $templatePath, $source) as $name) {
                $templatePathsByName[strtolower($name)][$templatePath] = $templatePath;
            }
        }

        return new TemplateAccesses(array_map(array_values(...), $templatePathsByName));
    }

    /**
     * Names after "." or "?." in the template's expressions.
     *
     * @return list<string>
     * @phpstan-pure
     */
    private static function accessedNamesIn(Environment $environment, string $templatePath, string $source): array
    {
        try {
            $tokens = $environment->tokenize(new Source($source, $templatePath));
        } catch (SyntaxError) {
            // Drupal cannot render it either.
            return [];
        }

        $names = [];
        $previous = null;
        while (!$tokens->isEOF()) {
            $token = $tokens->next();
            $name = $token->getValue();
            if (
                $previous !== null
                && self::isAccessOperator($previous)
                && $token->test(Token::NAME_TYPE)
                && is_string($name)
            ) {
                $names[] = $name;
            }
            $previous = $token;
        }

        return $names;
    }

    /**
     * Twig 3.21 and later lex the dot as an operator, earlier versions as
     * punctuation.
     *
     * @phpstan-pure
     */
    private static function isAccessOperator(Token $token): bool
    {
        return in_array($token->getType(), [Token::OPERATOR_TYPE, Token::PUNCTUATION_TYPE], true)
            && in_array($token->getValue(), ['.', '?.'], true);
    }
}
