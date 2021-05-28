<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

require_once 'vendor/autoload.php';

class FieldTest extends \PHPUnit_Framework_TestCase
{
    public function testDefault(): void
    {
        $field = new \HuCron\Field();

        $this->assertEquals('*', (string) $field);
    }

    public function testRepeat(): void
    {
        $field = new \HuCron\Field();

        $field->repeatsOn(2);

        $this->assertEquals('*/2', (string) $field);
    }

    public function testSpecific(): void
    {
        $field = new \HuCron\Field();

        $field->addSpecific(5)
              ->addSpecific(6);

        $this->assertEquals('5,6', (string) $field);

        $field->setSpecific([1,2,3,4]);

        $this->assertEquals('1,2,3,4', (string) $field);
    }

    public function testRepeatsWithSpecific(): void
    {
        $field = new \HuCron\Field();

        $field->repeatsOn(2)
              ->addSpecific(5);

        $this->assertEquals('*/2,5', (string) $field);
    }

    public function testRange(): void
    {
        $field = new \HuCron\Field();
        $field->setRange(0, 15);

        $this->assertEquals('0-15', (string) $field);

        $field->setRangeMin(3);
        $field->setRangeMax(20);

        $this->assertEquals('3-20', (string) $field);
    }
}
