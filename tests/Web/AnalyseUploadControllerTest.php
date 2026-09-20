<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Application\FindLongestPair;
use App\Domain\CollaborationCalculator;
use App\Input\ICsvReader;
use App\Input\PriorityFormatDateParser;
use App\Input\RowParser;
use App\Input\UnreadableFileException;
use App\Web\AnalyseUploadController;
use App\Web\PageModel;
use App\Web\UploadedCsvFile;
use App\Web\View;
use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\TestCase;

final class AnalyseUploadControllerTest extends TestCase
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

    public function testAGetRequestOnlyShowsTheForm(): void
    {
        $page = $this->controller()->handle(['REQUEST_METHOD' => 'GET'], []);

        self::assertFalse($page->hasResult());
        self::assertNull($page->error);
        self::assertSame([], $page->longest);
    }

    public function testAnUploadedFileIsAnalysed(): void
    {
        $path = $this->file(
            "143,12,2020-01-01,2020-01-10\n" .
            "412,12,2020-01-01,2020-01-10\n"
        );

        $page = $this->controller()->handle(['REQUEST_METHOD' => 'POST'], [
            'csv' => ['name' => 'employees.csv', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK],
        ]);

        self::assertTrue($page->hasResult());
        self::assertSame('employees.csv', $page->fileName);
        self::assertSame(10, $page->longest[0]->totalDays);
        self::assertNull($page->error);
    }

    public function testTheUserIsToldWhatTheyCanFixThemselves(): void
    {
        $page = $this->controller()->handle(['REQUEST_METHOD' => 'POST'], []);

        self::assertSame('Please choose a CSV file first.', $page->error);
        self::assertFalse($page->hasResult());
    }

    public function testAnUnexpectedFailureNeverReachesTheUser(): void
    {
        $path = $this->file("143,12,2020-01-01,NULL\n");

        $controller = new AnalyseUploadController(
            new FindLongestPair(
                new class () implements ICsvReader {
                    public function read(string $path): Generator
                    {
                        throw new UnreadableFileException(sprintf('File "%s" is gone.', $path));

                        yield from []; // @phpstan-ignore deadCode.unreachable
                    }
                },
                new RowParser(new PriorityFormatDateParser()),
                new CollaborationCalculator(),
            ),
            new UploadedCsvFile(static fn (string $file): bool => true),
        );

        $page = $controller->handle(['REQUEST_METHOD' => 'POST'], [
            'csv' => ['name' => 'employees.csv', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK],
        ]);

        self::assertSame('Something went wrong while reading the file. Please try again.', $page->error);
        self::assertStringNotContainsString($path, (string) $page->error, 'A server path must never be shown.');
    }

    public function testTheRenderedPageEscapesWhatItShows(): void
    {
        $html = (new View())->render('index', PageModel::failed('<script>alert(1)</script>'));

        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    private function controller(): AnalyseUploadController
    {
        return new AnalyseUploadController(
            FindLongestPair::create(new DateTimeImmutable('2020-06-15')),
            new UploadedCsvFile(static fn (string $path): bool => true),
        );
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
