<?php

declare(strict_types=1);

namespace App\Tests\Input;

use App\Input\CsvReader;
use App\Input\UnreadableFileException;
use PHPUnit\Framework\TestCase;

final class CsvReaderTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        $this->files = [];
    }

    public function testReadsRowsWithoutAHeader(): void
    {
        $path = $this->file(
            "143, 12, 2013-11-01, 2014-01-05\n" .
            "218, 10, 2012-05-16, NULL\n"
        );

        self::assertSame([
            1 => ['143', '12', '2013-11-01', '2014-01-05'],
            2 => ['218', '10', '2012-05-16', 'NULL'],
        ], iterator_to_array((new CsvReader())->read($path)));
    }

    public function testSkipsTheHeaderAndKeepsTheOriginalLineNumbers(): void
    {
        $path = $this->file(
            "EmpID,ProjectID,DateFrom,DateTo\n" .
            "143,12,2013-11-01,2014-01-05\n"
        );

        self::assertSame(
            [2 => ['143', '12', '2013-11-01', '2014-01-05']],
            iterator_to_array((new CsvReader())->read($path)),
        );
    }

    public function testSkipsEmptyLines(): void
    {
        $path = $this->file("143,12,2013-11-01,2014-01-05\n\n   \n,,,\n218,10,2012-05-16,NULL\n");

        $rows = iterator_to_array((new CsvReader())->read($path));

        self::assertSame([1, 5], array_keys($rows));
    }

    public function testStripsAByteOrderMark(): void
    {
        $path = $this->file("\xEF\xBB\xBF143,12,2013-11-01,2014-01-05\n");

        $rows = iterator_to_array((new CsvReader())->read($path));

        self::assertSame('143', $rows[1][0]);
    }

    public function testDetectsSemicolonAndTabDelimiters(): void
    {
        $semicolons = $this->file("143;12;2013-11-01;2014-01-05\n");
        $tabs = $this->file("143\t12\t2013-11-01\t2014-01-05\n");

        $expected = [1 => ['143', '12', '2013-11-01', '2014-01-05']];

        self::assertSame($expected, iterator_to_array((new CsvReader())->read($semicolons)));
        self::assertSame($expected, iterator_to_array((new CsvReader())->read($tabs)));
    }

    public function testKeepsAnEmptyTrailingValue(): void
    {
        $path = $this->file("143,12,2013-11-01,\n");

        self::assertSame(
            [1 => ['143', '12', '2013-11-01', '']],
            iterator_to_array((new CsvReader())->read($path)),
        );
    }

    public function testRejectsAMissingFile(): void
    {
        $this->expectException(UnreadableFileException::class);

        iterator_to_array((new CsvReader())->read(__DIR__ . '/does-not-exist.csv'));
    }

    public function testRejectsADirectory(): void
    {
        $this->expectException(UnreadableFileException::class);

        iterator_to_array((new CsvReader())->read(__DIR__));
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
