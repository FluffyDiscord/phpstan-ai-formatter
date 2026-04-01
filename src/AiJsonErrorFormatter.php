<?php

declare(strict_types=1);

namespace Webkult\PhpStanAiFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

/**
 * JSON error formatter optimized for AI/LLM parsing.
 * Produces structured, token-efficient JSON output.
 */
class AiJsonErrorFormatter implements ErrorFormatter
{
    public function __construct(private readonly RelativePathHelper $relativePathHelper)
    {
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $internalErrors = $analysisResult->getInternalErrorObjects();

        $result = [
            'totals' => [
                'errors' => count($analysisResult->getFileSpecificErrors()),
                'file_errors' => count($analysisResult->getFileSpecificErrors()),
                'warnings' => count($analysisResult->getWarnings()),
                'internal_errors' => count($internalErrors),
            ],
            'files' => [],
            'warnings' => [],
        ];

        // Group errors by file
        $errors = $analysisResult->getFileSpecificErrors();
        $groupedErrors = [];

        foreach ($errors as $error) {
            $file = $this->relativePathHelper->getRelativePath($error->getFilePath());
            if (!isset($groupedErrors[$file])) {
                $groupedErrors[$file] = [];
            }

            $groupedErrors[$file][] = [
                'line' => $error->getLine(),
                'message' => $this->shortenMessage($error->getMessage()),
                'ignorable' => $error->canBeIgnored(),
            ];
        }

        // Sort by file path
        ksort($groupedErrors);

        foreach ($groupedErrors as $file => $fileErrors) {
            // Sort by line number
            usort($fileErrors, static function ($a, $b): int {
                return ($a['line'] ?? 0) <=> ($b['line'] ?? 0);
            });

            $result['files'][$file] = $fileErrors;
        }

        // Warnings
        foreach ($analysisResult->getWarnings() as $warning) {
            $result['warnings'][] = $warning;
        }

        // Internal errors
        if (count($internalErrors) > 0) {
            $result['internal_errors'] = [];
            foreach ($internalErrors as $internalError) {
                $result['internal_errors'][] = ['message' => $internalError->getMessage()];
            }
        }

        // Output compact JSON
        $json = (string)json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $output->writeLineFormatted($json);

        return $analysisResult->hasErrors() ? 1 : 0;
    }

    /**
     * Shorten error messages to save tokens
     */
    private function shortenMessage(string $message): string
    {
        // Abbreviate common phrases
        $replacements = [
            'should return' => '→',
            'but returns' => 'returns',
            'does not accept' => '≠',
            'expects' => '→',
            'given' => 'got',
            'Parameter #' => 'P',
            'of method' => '',
            'Call to an undefined method' => 'Undefined:',
            'Access to an undefined property' => 'Undefined:',
        ];

        $shortened = str_replace(array_keys($replacements), array_values($replacements), $message);

        // Shorten long type unions
        $shortened = (string) preg_replace_callback(
            '/(\w+\|){4,}/',
            static function ($matches): string {
                $types = explode('|', rtrim($matches[0], '|'));
                return implode('|', array_slice($types, 0, 3)) . '|…';
            },
            $shortened
        );

        // Remove duplicate spaces
        $shortened = (string) preg_replace('/\s+/', ' ', $shortened);

        return trim($shortened);
    }
}
