<?php

declare(strict_types=1);

namespace Hackbard\PhpStanAiFormatter;

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
    private RelativePathHelper $relativePathHelper;

    public function __construct(RelativePathHelper $relativePathHelper)
    {
        $this->relativePathHelper = $relativePathHelper;
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $style = $output->getStyle();

        $result = [
            'totals' => [
                'errors' => count($analysisResult->getFileSpecificErrors()),
                'file_errors' => count($analysisResult->getFileSpecificErrors()),
                'warnings' => count($analysisResult->getWarnings()),
                'internal_errors' => count($analysisResult->getInternalErrors()),
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
                'tip' => $error->getTip() !== null ? $this->shortenMessage($error->getTip()) : null,
                'ignorable' => $error->canBeIgnored(),
            ];
        }

        // Sort by file path
        ksort($groupedErrors);
        
        foreach ($groupedErrors as $file => $fileErrors) {
            // Sort by line number
            usort($fileErrors, function ($a, $b) {
                return ($a['line'] ?? 0) <=> ($b['line'] ?? 0);
            });
            
            // Remove null tips to save tokens
            foreach ($fileErrors as &$error) {
                if ($error['tip'] === null) {
                    unset($error['tip']);
                }
            }
            
            $result['files'][$file] = $fileErrors;
        }

        // Warnings
        foreach ($analysisResult->getWarnings() as $warning) {
            $result['warnings'][] = $warning;
        }

        // Internal errors
        if (count($analysisResult->getInternalErrors()) > 0) {
            $result['internal_errors'] = [];
            foreach ($analysisResult->getInternalErrors() as $internalError) {
                $result['internal_errors'][] = [
                    'file' => $internalError->getFile() !== null 
                        ? $this->relativePathHelper->getRelativePath($internalError->getFile())
                        : null,
                    'message' => $internalError->getMessage(),
                ];
            }
        }

        // Output compact JSON
        $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $style->writeln($json);

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
        $shortened = preg_replace_callback(
            '/(\w+\|){4,}/',
            function ($matches) {
                $types = explode('|', rtrim($matches[0], '|'));
                return implode('|', array_slice($types, 0, 3)) . '|…';
            },
            $shortened
        );

        // Remove duplicate spaces
        $shortened = preg_replace('/\s+/', ' ', $shortened);

        return trim($shortened);
    }
}
