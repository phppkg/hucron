<?php /** @noinspection PhpUndefinedMethodInspection */
declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

use HuCron\Field;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    public function testDefault(): void
    {
        $field = new Field();

        $this->assertEquals('*', (string) $field);
    }

    public function testRepeat(): void
    {
        $field = new Field();

        $field->repeatsOn(2);

        $this->assertEquals('*/2', (string) $field);
    }

    public function testSpecific(): void
    {
        $field = new Field();

        $field->addSpecific(5)
              ->addSpecific(6);

        $this->assertEquals('5,6', (string) $field);

        $field->setSpecific([1,2,3,4]);

        $this->assertEquals('1,2,3,4', (string) $field);
    }

    public function testRepeatsWithSpecific(): void
    {
        $field = new Field();

        $field->repeatsOn(2)
              ->addSpecific(5);

        $this->assertEquals('*/2,5', (string) $field);
    }

    public function testRange(): void
    {
        $field = new Field();
        $field->setRange(0, 15);

        $this->assertEquals('0-15', (string) $field);

        $field->setRangeMin(3);
        $field->setRangeMax(20);

        $this->assertEquals('3-20', (string) $field);
    }
}
