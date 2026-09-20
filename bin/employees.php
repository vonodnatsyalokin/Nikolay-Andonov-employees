#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Application\FindLongestPair;
use App\Cli\EmployeesCommand;

require __DIR__ . '/../vendor/autoload.php';

$command = new EmployeesCommand(FindLongestPair::create());

exit($command->run(array_slice($argv, 1)));
