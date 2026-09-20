<?php

declare(strict_types=1);

namespace App\Tests\Cli;

use App\Application\FindLongestPair;
use App\Cli\EmployeesCommand;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EmployeesCommandTest extends TestCase
{
    /**
     * @var resource
     */
    private $output;

    /**
     * @var list<string>
     */
    private array $files = [];

    protected function setUp(): void
    {
        $output = fopen('php://memory', 'r+');
        self::assertIsResource($output);

        $this->output = $output;
    }

    protected function tearDown(): void
    {
        fclose($this->output);

        foreach ($this->files as $file) {
            @unlink($file);
        }

        $this->files = [];
    }

    public function testPrintsTheLongestPairAndItsProjects(): void
    {
        $path = $this->file(
            "143,12,2020-01-01,2020-01-10\n" .
            "412,12,2020-01-01,2020-01-10\n" .
            "143,14,2020-03-01,2020-03-05\n" .
            "412,14,2020-03-01,2020-03-31\n"
        );

        $exitCode = $this->execute($path);
        $output = $this->printed();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Longest working pair: 143 and 412, 15 days', $output);
        self::assertStringContainsString('Employee ID #1', $output);
        self::assertMatchesRegularExpression('/143\s+412\s+12\s+10/', $output);
        self::assertMatchesRegularExpression('/143\s+412\s+14\s+5/', $output);
    }

    public function testReportsSkippedRows(): void
    {
        $path = $this->file(
            "143,12,2020-01-01,2020-01-10\n" .
            "412,12,2020-01-01,2020-01-10\n" .
            "412,12,2020-13-45,NULL\n"
        );

        $this->execute($path);

        self::assertStringContainsString('Skipped 1 of 3 rows:', $this->printed());
        self::assertStringContainsString('line 3:', $this->printed());
    }

    public function testSaysSoWhenNobodyWorkedTogether(): void
    {
        $path = $this->file(
            "143,12,2009-01-01,2011-04-27\n" .
            "412,12,2012-05-16,2014-01-01\n"
        );

        self::assertSame(0, $this->execute($path));
        self::assertStringContainsString('No pair of employees', $this->printed());
    }

    public function testMarksTiedPairs(): void
    {
        $path = $this->file(
            "100,12,2020-01-01,2020-01-10\n" .
            "200,12,2020-01-01,2020-01-10\n" .
            "300,14,2020-01-01,2020-01-10\n" .
            "400,14,2020-01-01,2020-01-10\n"
        );

        $this->execute($path);

        self::assertSame(2, substr_count($this->printed(), 'Tied longest pair'));
    }

    public function testFailsWithoutAFileArgument(): void
    {
        $command = new EmployeesCommand(
            FindLongestPair::create(new DateTimeImmutable('2020-06-15')),
            $this->output,
        );

        self::assertSame(1, $command->run([]));
        self::assertStringContainsString('Usage:', $this->printed());
    }

    public function testFailsOnAMissingFile(): void
    {
        self::assertSame(1, $this->execute(__DIR__ . '/does-not-exist.csv'));
        self::assertStringContainsString('Error:', $this->printed());
    }

    private function execute(string $path): int
    {
        $command = new EmployeesCommand(
            FindLongestPair::create(new DateTimeImmutable('2020-06-15')),
            $this->output,
        );

        return $command->run([$path]);
    }

    private function printed(): string
    {
        rewind($this->output);

        return (string) stream_get_contents($this->output);
    }

    private function file(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'employees-test-');
        self::assertIsString($path);

        file_put_contents($path, $contents);
        $this->files[] = $path;

        return $path;
    }
}
