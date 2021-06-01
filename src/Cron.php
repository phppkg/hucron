<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

namespace HuCron;

use InvalidArgumentException;
use function count;
use function implode;
use function preg_split;
use function strtolower;
use function trim;
use const PREG_SPLIT_NO_EMPTY;

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
     * @var string
     */
    private $rawExpr = '';

    /**
     * eg '5 10 * * *'
     *
     * @var string
     */
    private $cronExpr;

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

    /**
     * @param string $string
     *
     * @return static
     */
    public static function new(string $string = ''): self
    {
        return new self($string);
    }

    /**
     * Class constructor.
     *
     * @param string $string
     */
    public function __construct(string $string = '')
    {
        $this->dayOfWeek  = Field::new(Field::DAY_OF_WEEK);
        $this->dayOfMonth = Field::new(Field::DAY_OF_MONTH);

        $this->month  = Field::month();
        $this->hour   = Field::hour();
        $this->minute = Field::minute();

        if ($string) {
            $this->fromString($string);
        }
    }

    /**
     * @return string
     */
    public function toStatement(): string
    {
        return Statement::fromCron($this)->toStatement();
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
     * @param string $string eg: '5 10 * * *'
     */
    public function fromString(string $string): void
    {
        $fmtStr = strtolower(trim($string));
        $parts  = preg_split('/\s+/', $fmtStr, -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) < 5) {
            throw new InvalidArgumentException("invalid cron expr string: $string");
        }

        $this->rawExpr = $string;

        // assign to vars
        [$minute, $hour, $dayOfMonth, $month, $dayOfWeek] = $parts;

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
        return $this->getCronExpr();
    }

    /**
     * @param string $whitespace
     */
    public function setWhitespace(string $whitespace): void
    {
        $this->whitespace = $whitespace;
    }

    /**
     * @return string
     */
    public function getCronExpr(): string
    {
        if (!$this->cronExpr) {
            $this->cronExpr = implode($this->whitespace, $this->ordered());
        }

        return $this->cronExpr;
    }

    /**
     * @return string
     */
    public function getRawExpr(): string
    {
        return $this->rawExpr;
    }
}
