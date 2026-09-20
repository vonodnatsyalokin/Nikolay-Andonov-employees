<?php

declare(strict_types=1);

namespace App\Web;

use App\Application\FindLongestPair;
use Throwable;

/**
 * Takes the uploaded file and turns it into everything the page shows.
 */
final readonly class AnalyseUploadController
{
    private const GENERIC_ERROR = 'Something went wrong while reading the file. Please try again.';

    public function __construct(
        private FindLongestPair $service,
        private UploadedCsvFile $upload,
    ) {
    }

    public static function create(): self
    {
        return new self(FindLongestPair::create(), new UploadedCsvFile());
    }

    /**
     * @param array<array-key, mixed> $server the request, i.e. $_SERVER
     * @param array<array-key, mixed> $files  the uploads, i.e. $_FILES
     */
    public function handle(array $server, array $files): PageModel
    {
        if (($server['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return PageModel::empty();
        }

        $file = self::fileEntry($files['csv'] ?? null);

        try {
            $path = $this->upload->path($file);
        } catch (UploadException $exception) {
            // The user can fix this one themselves, so they get to read it.
            return PageModel::failed($exception->getMessage());
        }

        $name = is_string($file['name'] ?? null) ? $file['name'] : 'the uploaded file';

        try {
            return PageModel::of($this->service->inFile($path), $name);
        } catch (Throwable $exception) {
            // TODO: log $exception here. Its message can name a path on the
            // server, so the page only gets something safe to read.
            return PageModel::failed(self::GENERIC_ERROR);
        }
    }

    /**
     * One entry of $_FILES, with the keys PHP itself puts there.
     *
     * @return array<string, mixed>|null
     */
    private static function fileEntry(mixed $entry): ?array
    {
        if (!is_array($entry)) {
            return null;
        }

        $file = [];

        foreach ($entry as $key => $value) {
            if (is_string($key)) {
                $file[$key] = $value;
            }
        }

        return $file;
    }
}
