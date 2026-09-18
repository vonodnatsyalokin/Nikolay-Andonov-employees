<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\EmployeePair;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmployeePairTest extends TestCase
{
    public function testOrderOfTheEmployeesDoesNotMatter(): void
    {
        $one = new EmployeePair(218, 143);
        $other = new EmployeePair(143, 218);

        self::assertSame(143, $one->firstEmployeeId);
        self::assertSame(218, $one->secondEmployeeId);
        self::assertSame($other->key(), $one->key());
        self::assertSame('143-218', $one->key());
    }

    public function testRejectsAnEmployeePairedWithThemselves(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EmployeePair(143, 143);
    }
}
