<?php

declare(strict_types=1);

namespace Webkult\PhpStanAiFormatter\Tests;

use Webkult\PhpStanAiFormatter\AiErrorFormatter;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class AiErrorFormatterTest extends TestCase
{
    private AiErrorFormatter $formatter;
    private RelativePathHelper $relativePathHelper;

    protected function setUp(): void
    {
        $this->relativePathHelper = new RelativePathHelper(
            '/project',
            '/',
            []
        );
        
        $this->formatter = new AiErrorFormatter($this->relativePathHelper);
    }

    public function testFormatNoErrors(): void
    {
        $analysisResult = new AnalysisResult(
            [],
            [],
            [],
            [],
            [],
            false,
            null,
            true,
            0,
            false,
            null
        );

        $bufferedOutput = new BufferedOutput();
        $output = new Output($bufferedOutput, new SymfonyStyle(new StringInput(''), $bufferedOutput));

        $exitCode = $this->formatter->formatErrors($analysisResult, $output);

        $this->assertSame(0, $exitCode);
        $outputText = $bufferedOutput->fetch();
        $this->assertStringContainsString('No errors', $outputText);
    }

    public function testMessageShortening(): void
    {
        // Test via reflection since shortenMessage is private
        $reflection = new \ReflectionClass($this->formatter);
        $method = $reflection->getMethod('shortenMessage');
        $method->setAccessible(true);

        $testCases = [
            [
                'input' => 'Method App\Service::getUser() should return App\User but returns App\User|null.',
                'contains' => '→',
            ],
            [
                'input' => 'Call to an undefined method Repository::find().',
                'contains' => 'Undefined method',
            ],
            [
                'input' => 'Property App\User::$email (string) does not accept int.',
                'contains' => 'Property $email',
            ],
        ];

        foreach ($testCases as $testCase) {
            $result = $method->invoke($this->formatter, $testCase['input']);
            $this->assertLessThan(
                strlen($testCase['input']),
                strlen($result),
                "Message should be shortened: {$testCase['input']}"
            );
            
            if (isset($testCase['contains'])) {
                $this->assertStringContainsString(
                    $testCase['contains'],
                    $result,
                    "Shortened message should contain: {$testCase['contains']}"
                );
            }
        }
    }
}
