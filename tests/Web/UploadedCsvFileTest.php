<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Web\UploadedCsvFile;
use App\Web\UploadException;
use PHPUnit\Framework\TestCase;

final class UploadedCsvFileTest extends TestCase
{
    public function testAcceptsAnUploadedCsvFile(): void
    {
        $path = $this->upload()->path([
            'name' => 'employees.csv',
            'tmp_name' => '/tmp/php-upload-123',
            'error' => UPLOAD_ERR_OK,
        ]);

        self::assertSame('/tmp/php-upload-123', $path);
    }

    public function testAcceptsATextFile(): void
    {
        $path = $this->upload()->path([
            'name' => 'EMPLOYEES.TXT',
            'tmp_name' => '/tmp/php-upload-123',
            'error' => UPLOAD_ERR_OK,
        ]);

        self::assertSame('/tmp/php-upload-123', $path);
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

        $this->upload()->path([
            'name' => 'employees.csv',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_INI_SIZE,
        ]);
    }

    public function testRejectsAFileThatWasNeverUploaded(): void
    {
        $upload = new UploadedCsvFile(static fn (string $path): bool => false);

        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('could not be read');

        // A crafted request pointing at a file that already sits on the server.
        $upload->path([
            'name' => 'employees.csv',
            'tmp_name' => '/etc/passwd',
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    public function testRejectsAnUnexpectedFileType(): void
    {
        $this->expectException(UploadException::class);
        $this->expectExceptionMessage('.csv or .txt');

        $this->upload()->path([
            'name' => 'employees.pdf',
            'tmp_name' => '/tmp/php-upload-123',
            'error' => UPLOAD_ERR_OK,
        ]);
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
}
