<?php

declare(strict_types=1);

namespace Webkult\PhpStanAiFormatter\Tests;

use Webkult\PhpStanAiFormatter\AiErrorFormatter;
use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\Command\OutputStyle;
use PHPStan\File\SimpleRelativePathHelper;
use PHPUnit\Framework\TestCase;

class AiErrorFormatterTest extends TestCase
{
    private AiErrorFormatter $formatter;

    protected function setUp(): void
    {
        $relativePathHelper = new SimpleRelativePathHelper('/project');
        $this->formatter = new AiErrorFormatter($relativePathHelper);
    }

    private function createOutput(): array
    {
        $capture = (object) ['lines' => []];

        $style = $this->createMock(OutputStyle::class);
        $style->method('success')->willReturnCallback(function (string $msg) use ($capture): void {
            $capture->lines[] = '[success] ' . $msg;
        });
        $style->method('note')->willReturnCallback(function (string $msg) use ($capture): void {
            $capture->lines[] = '[note] ' . $msg;
        });
        $style->method('title')->willReturnCallback(function (string $msg) use ($capture): void {
            $capture->lines[] = '[title] ' . $msg;
        });
        $style->method('section')->willReturnCallback(function (string $msg) use ($capture): void {
            $capture->lines[] = '[section] ' . $msg;
        });

        $output = $this->createMock(Output::class);
        $output->method('getStyle')->willReturn($style);
        $output->method('writeLineFormatted')->willReturnCallback(function (string $msg) use ($capture): void {
            $capture->lines[] = $msg;
        });

        return [$output, $capture];
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
            []
        );

        [$output, $capture] = $this->createOutput();

        $exitCode = $this->formatter->formatErrors($analysisResult, $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No errors', implode("\n", $capture->lines));
    }

    public function testMessageShortening(): void
    {
        $reflection = new \ReflectionClass($this->formatter);
        $method = $reflection->getMethod('shortenMessage');

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

    public function testFilePathDeduplication(): void
    {
        $errors = [
            new Error('Method param $user: User|null ≠ User', '/project/src/UserService.php', 23),
            new Error('getUser() → User|null (expected User)', '/project/src/UserService.php', 45),
            new Error('Undefined property: $name', '/project/src/UserService.php', 60),
        ];

        $analysisResult = new AnalysisResult(
            $errors,
            [],
            [],
            [],
            [],
            false,
            null,
            true,
            0,
            false,
            []
        );

        [$output, $capture] = $this->createOutput();

        $exitCode = $this->formatter->formatErrors($analysisResult, $output);

        $this->assertSame(1, $exitCode);

        $allOutput = implode("\n", $capture->lines);

        // First error: full path
        $this->assertStringContainsString('src/UserService.php:23', $allOutput);
        // Middle error: ╠ connector
        $this->assertStringContainsString('╠:45', $allOutput);
        // Last error: ╚ connector
        $this->assertStringContainsString('╚:60', $allOutput);
        // File path should NOT be repeated on subsequent lines
        $pathLines = array_filter($capture->lines, static fn($l) => str_contains($l, 'src/UserService.php'));
        $this->assertCount(1, $pathLines, 'File path should only appear once');
    }
}
