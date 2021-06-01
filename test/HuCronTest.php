<?php declare(strict_types=1);

/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

namespace HuCronTest;

use HuCron\HuCron;
use HuCron\Parser;
use PHPUnit\Framework\TestCase;

class HuCronTest extends TestCase
{
    public function testFromStatement(): void
    {
        $cronString = HuCron::fromStatement('Every day at midnight');
        $this->assertNotEmpty($cronString);

        $cronExpr = HuCron::fromStatement('Every 5 min');
        $this->assertEquals('*/5 * * * *', $cronExpr);

        $cronExpr = HuCron::fromStatement('Every 5 mins');
        $this->assertEquals('*/5 * * * *', $cronExpr);
    }

    public function testGetParser(): void
    {
        $this->assertInstanceOf(Parser::class, HuCron::getParser());
    }
}
