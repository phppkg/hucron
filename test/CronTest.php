<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

require_once 'vendor/autoload.php';

use HuCron\Cron;

class CronTest extends \PHPUnit_Framework_TestCase
{
    public function testFromString(): void
    {
        $cron = new Cron();

        $cron->fromString('25 2-6 */2 * 1,2,3,4,5');

        $this->assertEquals(
            [25],
            $cron->minute->getSpecific()
        );

        $this->assertEquals(
            2,
            $cron->hour->getRangeMin()
        );

        $this->assertEquals(
            6,
            $cron->hour->getRangeMax()
        );

        $this->assertEquals(
            2,
            $cron->dayOfMonth->getRepeats()
        );

        $this->assertEquals(
            [1,2,3,4,5],
            $cron->dayOfWeek->getSpecific()
        );

        // From constructor
        $cron = new Cron('0 30  * * *');

        $this->assertEquals(
            '0 30 * * *',
            (string) $cron
        );
    }

    public function testSetWhitespace(): void
    {
        $cron = new Cron();

        $cron->setWhitespace('    ');

        $this->assertEquals(
            '*    *    *    *    *',
            (string) $cron
        );
    }
}
