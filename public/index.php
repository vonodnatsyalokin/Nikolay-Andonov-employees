<?php

declare(strict_types=1);

use App\Web\AnalyseUploadController;
use App\Web\View;

require __DIR__ . '/../vendor/autoload.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$page = AnalyseUploadController::create()->handle($_SERVER, $_FILES);

echo (new View())->render('index', $page);
