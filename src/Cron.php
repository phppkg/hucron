<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

namespace HuCron;

use function preg_split;

/**
 * Represents a CRON expression
 *
 * Class Cron
 *
 * @package HuCron
 */
class Cron
{
    /**
     * @var Field
     */
    public $dayOfWeek;

    /**
     * @var Field
     */
    public $month;

    /**
     * @var Field
     */
    public $dayOfMonth;

    /**
     * @var Field
     */
    public $hour;

    /**
     * @var Field
     */
    public $minute;

    /**
     * @var string
     */
    protected $whitespace = ' ';

    public function __construct(string $string = null)
    {
        $this->dayOfWeek  = new Field();
        $this->dayOfMonth = new Field();

        $this->month  = new Field();
        $this->hour   = new Field();
        $this->minute = new Field();

        if ($string) {
            $this->fromString($string);
        }
    }

    /**
     * Get CRON expression field order in structured format
     *
     * @return array
     */
    public function ordered(): array
    {
        return [
            $this->minute,
            $this->hour,
            $this->dayOfMonth,
            $this->month,
            $this->dayOfWeek
        ];
    }

    /**
     * Create CRON object from a crontab string
     *
     * @param string $string
     */
    public function fromString(string $string): void
    {
        [
            $minute,
            $hour,
            $dayOfMonth,
            $month,
            $dayOfWeek
        ] = preg_split('/\s+/', $string);

        $this->minute->fromCronValue($minute);
        $this->hour->fromCronValue($hour);
        $this->month->fromCronValue($month);
        $this->dayOfWeek->fromCronValue($dayOfWeek);
        $this->dayOfMonth->fromCronValue($dayOfMonth);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return trim(implode($this->whitespace, $this->ordered()));
    }

    /**
     * @param string $whitespace
     */
    public function setWhitespace(string $whitespace): void
    {
        $this->whitespace = $whitespace;
    }
}
