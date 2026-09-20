<?php

declare(strict_types=1);

use App\Application\AnalysisResult;
use App\Application\FindLongestPair;
use App\Input\UnreadableFileException;
use App\Web\UploadedCsvFile;
use App\Web\UploadException;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Everything that reaches the page goes through here first.
 */
function e(string|int $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error = null;
$fileName = null;
$result = null;

/**
 * One entry of $_FILES, with the keys PHP itself puts there.
 *
 * @return array<string, mixed>|null
 */
function uploadedFile(mixed $entry): ?array
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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $upload = uploadedFile($_FILES['csv'] ?? null);

    try {
        $path = (new UploadedCsvFile())->path($upload);
        $fileName = is_string($upload['name'] ?? null) ? $upload['name'] : 'the uploaded file';
        $result = FindLongestPair::create()->inFile($path);
    } catch (UploadException | UnreadableFileException $exception) {
        $error = $exception->getMessage();
    }
}

$longest = $result instanceof AnalysisResult ? $result->longest() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employees who worked together</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
    <h1>Employees who worked together</h1>
    <p class="lead">
        Upload a CSV file with <code>EmpID, ProjectID, DateFrom, DateTo</code> to see which pair of
        employees has worked together on common projects for the longest time.
    </p>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="csv" accept=".csv,.txt" required>
        <button type="submit">Find the pair</button>
    </form>

    <?php if ($error !== null) { ?>
        <p class="error"><?= e($error) ?></p>
    <?php } ?>

    <?php if ($result instanceof AnalysisResult) { ?>
        <?php if ($longest === []) { ?>
            <p class="empty">
                No pair of employees in <?= e((string) $fileName) ?> has worked together on a common project.
            </p>
        <?php } else { ?>
            <?php
                $pairNames = array_map(
                    static fn ($collaboration): string => $collaboration->pair->firstEmployeeId
                        . ' & ' . $collaboration->pair->secondEmployeeId,
                    $longest,
                );
            ?>
            <h2>
                <?= count($longest) > 1 ? 'Longest working pairs' : 'Longest working pair' ?>:
                <?= e(implode(', ', $pairNames)) ?> &mdash; <?= e($longest[0]->totalDays) ?> days
            </h2>

            <table>
                <thead>
                <tr>
                    <th>Employee ID #1</th>
                    <th>Employee ID #2</th>
                    <th>Project ID</th>
                    <th>Days worked</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($longest as $collaboration) { ?>
                    <?php foreach ($collaboration->projects as $project) { ?>
                        <tr>
                            <td><?= e($collaboration->pair->firstEmployeeId) ?></td>
                            <td><?= e($collaboration->pair->secondEmployeeId) ?></td>
                            <td><?= e($project->projectId) ?></td>
                            <td><?= e($project->days) ?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>

        <?php if ($result->skippedRows !== []) { ?>
            <div class="warning">
                <p><?= e(count($result->skippedRows)) ?> of <?= e($result->rowsRead) ?> rows were skipped:</p>
                <ul>
                    <?php foreach ($result->skippedRows as $row) { ?>
                        <li>Line <?= e($row->lineNumber) ?>: <?= e($row->reason) ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    <?php } ?>
</main>
</body>
</html>
