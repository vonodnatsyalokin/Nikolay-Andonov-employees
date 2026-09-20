<?php

declare(strict_types=1);

namespace App\Web;

use Closure;
use finfo;

/**
 * Checks what the browser sent before anything is read from disk.
 */
final class UploadedCsvFile
{
    private const ALLOWED_EXTENSIONS = ['csv', 'txt'];

    /**
     * A limit of our own, so it does not silently change with php.ini.
     */
    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * A CSV file is plain text. Anything a browser would call a document,
     * an image or an archive is not one, whatever the file is named.
     */
    private const ALLOWED_MIME_TYPES = [
        'text/plain',
        'text/csv',
        'application/csv',
        'inode/x-empty',
        'application/x-empty', // an empty file, named differently by different systems
    ];

    private const ERRORS = [
        UPLOAD_ERR_INI_SIZE => 'The file is larger than this server allows.',
        UPLOAD_ERR_FORM_SIZE => 'The file is larger than the form allows.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded, please try again.',
        UPLOAD_ERR_NO_FILE => 'Please choose a CSV file first.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server has no temporary folder to store the upload in.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
    ];

    private readonly Closure $isUploadedFile;

    /**
     * @param (callable(string): bool)|null $isUploadedFile injectable so the check can be tested
     */
    public function __construct(?callable $isUploadedFile = null)
    {
        $this->isUploadedFile = Closure::fromCallable($isUploadedFile ?? 'is_uploaded_file');
    }

    /**
     * @param array<string, mixed>|null $file one entry of $_FILES
     *
     * @return string path of the uploaded file on this server
     *
     * @throws UploadException
     */
    public function path(?array $file = null): string
    {
        if ($file === null) {
            throw new UploadException(self::ERRORS[UPLOAD_ERR_NO_FILE]);
        }

        $error = is_int($file['error'] ?? null) ? $file['error'] : UPLOAD_ERR_NO_FILE;

        if ($error !== UPLOAD_ERR_OK) {
            throw new UploadException(self::ERRORS[$error] ?? 'The upload failed.');
        }

        $path = is_string($file['tmp_name'] ?? null) ? $file['tmp_name'] : '';

        // Guards against a request that points at a file on the server instead of an upload.
        if ($path === '' || !($this->isUploadedFile)($path)) {
            throw new UploadException('The upload could not be read.');
        }

        $this->guardExtension(is_string($file['name'] ?? null) ? $file['name'] : '');
        $this->guardSize($path);
        $this->guardContents($path);

        return $path;
    }

    private function guardSize(string $path): void
    {
        if ((int) filesize($path) > self::MAX_BYTES) {
            throw new UploadException(sprintf(
                'The file is larger than %d MB.',
                intdiv(self::MAX_BYTES, 1024 * 1024),
            ));
        }
    }

    /**
     * The name says .csv, but only the contents can say whether it is text.
     * Most CSV files are reported as text/plain, so this rules out binaries
     * rather than proving the file is a CSV.
     */
    private function guardContents(string $path): void
    {
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new UploadException('That does not look like a text file.');
        }
    }

    private function guardExtension(string $name): void
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new UploadException(sprintf(
                'Please upload a %s file.',
                implode(' or ', array_map(static fn (string $e): string => '.' . $e, self::ALLOWED_EXTENSIONS)),
            ));
        }
    }
}
