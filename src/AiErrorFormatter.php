<?php

declare(strict_types=1);

namespace Webkult\PhpStanAiFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

/**
 * AI-optimized error formatter that produces compact, token-efficient output.
 * Perfect for use with Claude, ChatGPT, and other LLMs.
 */
class AiErrorFormatter implements ErrorFormatter
{
    public function __construct(private readonly RelativePathHelper $relativePathHelper)
    {
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $projectConfigFile = 'phpstan.neon';
        if ($analysisResult->getProjectConfigFile() !== null) {
            $projectConfigFile = $this->relativePathHelper->getRelativePath(
                $analysisResult->getProjectConfigFile()
            );
        }

        $style = $output->getStyle();

        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            $style->success('No errors found!');

            if ($analysisResult->isDefaultLevelUsed()) {
                $style->note('Increase analysis level for deeper inspection (--level=max)');
            }

            return 0;
        }

        // Header with summary
        $errorCount = count($analysisResult->getFileSpecificErrors());
        $warningCount = count($analysisResult->getWarnings());

        $style->title(sprintf(
            'PHPStan Analysis (config: %s)',
            $projectConfigFile
        ));

        $output->writeLineFormatted(sprintf(
            'Found: %d error%s, %d warning%s',
            $errorCount,
            $errorCount === 1 ? '' : 's',
            $warningCount,
            $warningCount === 1 ? '' : 's'
        ));

        $style->newLine();

        // Compact error output
        $style->section('Errors');

        $errors = $analysisResult->getFileSpecificErrors();
        $groupedErrors = $this->groupErrorsByFile($errors);

        foreach ($groupedErrors as $file => $fileErrors) {
            $relativePath = $this->relativePathHelper->getRelativePath($file);
            $count = count($fileErrors);

            foreach ($fileErrors as $index => $error) {
                $line = $error->getLine() ?? 0;
                $message = $this->shortenMessage($error->getMessage());

                if ($index === 0) {
                    $prefix = sprintf('<fg=red>%s:%s</>', $relativePath, $line);
                } elseif ($index === $count - 1) {
                    $prefix = sprintf('<fg=red>╚:%s</>', $line);
                } else {
                    $prefix = sprintf('<fg=red>╠:%s</>', $line);
                }

                $output->writeLineFormatted(sprintf('%s | %s', $prefix, $message));

                if ($error->getTip() !== null) {
                    $output->writeLineFormatted(sprintf('  → %s', $this->shortenMessage($error->getTip())));
                }
            }
        }

        // Warnings (if any)
        if ($warningCount > 0) {
            $style->newLine();
            $style->section('Warnings');

            foreach ($analysisResult->getWarnings() as $warning) {
                $output->writeLineFormatted(sprintf('<fg=yellow>⚠</> %s', $warning));
            }
        }

        // Internal errors (if any)
        $internalErrors = $analysisResult->getInternalErrorObjects();
        if (count($internalErrors) > 0) {
            $style->newLine();
            $style->section('Internal Errors');

            foreach ($internalErrors as $internalError) {
                $output->writeLineFormatted(sprintf('<fg=red>internal</> | %s', $internalError->getMessage()));
            }
        }

        $style->newLine();

        return $analysisResult->hasErrors() ? 1 : 0;
    }

    /**
     * Group errors by file for better organization
     *
     * @param array<Error> $errors
     * @return array<string, array<Error>>
     */
    private function groupErrorsByFile(array $errors): array
    {
        $grouped = [];

        foreach ($errors as $error) {
            $file = $error->getFilePath();
            if (!isset($grouped[$file])) {
                $grouped[$file] = [];
            }
            $grouped[$file][] = $error;
        }

        // Sort by file path
        ksort($grouped);

        // Sort errors within each file by line number
        foreach ($grouped as &$fileErrors) {
            usort($fileErrors, static function ($a, $b): int {
                return ($a->getLine() ?? 0) <=> ($b->getLine() ?? 0);
            });
        }

        return $grouped;
    }

    /**
     * Shorten error messages by removing redundant phrases
     */
    private function shortenMessage(string $message): string
    {
        // Remove common prefixes that don't add value
        $patterns = [
            '/^Parameter #\d+ \$(\w+) of method /' => 'Method param $\1: ',
            '/^Method .+?::(\w+)\(\) should return (.+?) but returns (.+?)\.$/' => '\1() → \3 (expected \2)',
            '/^Property .+?::\$(\w+) \((.+?)\) does not accept (.+?)\.$/' => 'Property $\1: \3 ≠ \2',
            '/^Call to an undefined method .+?::(\w+)\(\)\.$/' => 'Undefined method: \1()',
            '/^Access to an undefined property .+?::\$(\w+)\.$/' => 'Undefined property: $\1',
            '/^Parameter #\d+ \$\w+ of function (\w+) expects (.+?), (.+?) given\.$/' => '\1() expects \2, got \3',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $message = (string) preg_replace($pattern, $replacement, $message);
        }

        // Shorten long type unions (keep first 3 types + ...)
        $message = (string) preg_replace_callback(
            '/(\w+\|){4,}/',
            static function ($matches): string {
                $types = explode('|', rtrim($matches[0], '|'));
                return implode('|', array_slice($types, 0, 3)) . '|...';
            },
            $message
        );

        return $message;
    }
}
