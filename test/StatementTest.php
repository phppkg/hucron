<?php declare(strict_types=1);

namespace HuCronTest;

use HuCron\Statement;
use PHPUnit\Framework\TestCase;

/**
 * Class StatementTest
 *
 * @package HuCronTest
 */
class StatementTest extends TestCase
{
    public function testConvert(): void
    {
        $st = Statement::fromCronString('20 10 * * *');

        // vdump($st->convert());
        self::assertNotEmpty($st->convert());
    }
}
