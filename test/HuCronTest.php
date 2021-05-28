<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

require_once 'vendor/autoload.php';

class HuCronTest extends \PHPUnit_Framework_TestCase
{
    public function testCron(): void
    {
        $this->assertInternalType('string', \HuCron\HuCron::fromExpression('Every day at midnight'));
    }

    public function testGetParser(): void
    {
        $this->assertInstanceOf('HuCron\Parser', \HuCron\HuCron::getParser());
    }
}
