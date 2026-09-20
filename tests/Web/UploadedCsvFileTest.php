<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Web\UploadedCsvFile;
use App\Web\UploadException;
use PHPUnit\Framework\TestCase;

final class UploadedCsvFileTest extends TestCase
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

    public function testAcceptsAnUploadedCsvFile(): void
    {
        $path = $this->file("143,12,2020-01-01,NULL\n");

        self::assertSame($path, $this->upload()->path($this->entry($path, 'employees.csv')));
    }

    public function testAcceptsATextFileAndAnEmptyOne(): void
    {
        $text = $this->file("143,12,2020-01-01,NULL\n");
        $empty = $this->file('');

        self::assertSame($text, $this->upload()->path($this->entry($text, 'EMPLOYEES.TXT')));
        self::assertSame($empty, $this->upload()->path($this->entry($empty, 'employees.csv')));
    }

    public function testRejectsAMissingUpload(): void
    {
        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('Please choose a CSV file first.');

        $this->upload()->path(null);
    }

    public function testExplainsWhyTheUploadFailed(): void
    {
        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('larger than this server allows');

        $this->upload()->path(['name' => 'employees.csv', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE]);
    }

    public function testRejectsAFileThatWasNeverUploaded(): void
    {
        $upload = new UploadedCsvFile(static fn (string $path): bool => false);

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('could not be read');

        // A crafted request pointing at a file that already sits on the server.
        $upload->path($this->entry('/etc/passwd', 'employees.csv'));
    }

    public function testRejectsAnUnexpectedFileType(): void
    {
        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('.csv or .txt');

        $this->upload()->path($this->entry($this->file('143,12,2020-01-01,NULL'), 'employees.pdf'));
    }

    public function testRejectsAFileThatIsNotText(): void
    {
        // A PNG renamed to .csv: the name says one thing, the bytes say another.
        $png = $this->file("\x89PNG\r\n\x1a\n" . str_repeat("\x00\x01\x02\x03", 64));

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('does not look like a text file');

        $this->upload()->path($this->entry($png, 'employees.csv'));
    }

    public function testRejectsAFileOverTheSizeLimit(): void
    {
        $tooBig = $this->file(str_repeat("143,12,2020-01-01,NULL\n", 250_000)); // ~5.5 MB

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('larger than 5 MB');

        $this->upload()->path($this->entry($tooBig, 'employees.csv'));
    }

    public function testRejectsAMalformedFilesEntry(): void
    {
        $this->expectException(UploadException::class);

        $this->upload()->path(['name' => 'employees.csv']);
    }

    private function upload(): UploadedCsvFile
    {
        return new UploadedCsvFile(static fn (string $path): bool => true);
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(string $path, string $name): array
    {
        return ['name' => $name, 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK];
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
