<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

use HuCron\Cron;
use PHPUnit\Framework\TestCase;

class CronTest extends TestCase
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
