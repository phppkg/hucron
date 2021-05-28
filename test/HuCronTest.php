<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

use HuCron\HuCron;
use HuCron\Parser;
use PHPUnit\Framework\TestCase;

class HuCronTest extends TestCase
{
    public function testCron(): void
    {
        $this->assertInternalType('string', HuCron::fromExpr('Every day at midnight'));
    }

    public function testGetParser(): void
    {
        $this->assertInstanceOf(Parser::class, HuCron::getParser());
    }
}
