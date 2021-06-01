<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

namespace HuCron;

use function explode;
use function implode;
use function is_null;
use function preg_match;
use function strpos;

/**
 * Class Field - Represents a field within a CRON expression
 *
 * @package HuCron
 */
class Field
{
    // '5 10 * * *'
    public const MINUTE       = 'minute';
    public const HOUR         = 'hour';
    public const DAY_OF_MONTH = 'dayOfMonth';
    public const MONTH        = 'month';
    public const DAY_OF_WEEK  = 'dayOfWeek';

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    protected $repeats;

    /**
     * @var int[]
     */
    protected $specific = [];

    /**
     * @var int|null
     */
    protected $rangeMin;

    /**
     * @var int|null
     */
    protected $rangeMax;

    /**
     * @return static
     */
    public static function minute(): self
    {
        return new self(self::MINUTE);
    }

    /**
     * @return static
     */
    public static function hour(): self
    {
        return new self(self::HOUR);
    }

    /**
     * @return static
     */
    public static function month(): self
    {
        return new self(self::MONTH);
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public static function new(string $name): self
    {
        return new self($name);
    }

    /**
     * Class constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Build CRON expression part based on set values
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Build CRON expression part based on set values
     *
     * @return string
     */
    public function toString(): string
    {
        $value = '';
        if ($this->repeats) {
            $value = '*/' . $this->repeats;
        }

        if (count($this->specific) > 0) {
            if ($value !== '') {
                $value .= ',';
            }
            $value .= implode(',', $this->specific);
        }

        // TODO use if (isset($this->rangeMin, $this->rangeMax)
        if (!is_null($this->rangeMin) && !is_null($this->rangeMax)
            && $this->rangeMin >= 0 && $this->rangeMax >= 0
        ) {
            $value = $this->rangeMin . '-' . $this->rangeMax;
        }

        return $value === '' ? '*' : $value;
    }

    /**
     * @param string $val
     */
    public function fromCronValue(string $val): void
    {
        $parts = explode(',', $val);

        foreach ($parts as $part) {
            if (strpos($part, '/') !== false) {
                preg_match('/\*\/(\d+)/', $part, $matches);

                if (isset($matches[1])) {
                    $this->repeatsOn((int)$matches[1]);
                }
            } elseif (strpos($part, '-') !== false) {
                $ranges = explode('-', $part);
                $this->setRange((int)$ranges[0], (int)$ranges[1]);
            } elseif (is_numeric($part)) {
                $this->addSpecific((int)$part);
            }
        }
    }

    /**
     * @return bool
     */
    public function isDirty(): bool
    {
        return !is_null($this->repeats) || !is_null($this->rangeMin)
            || !is_null($this->rangeMax) || count($this->specific) > 0;
    }

    /**
     * @param int $min
     * @param int $max
     *
     * @return $this
     */
    public function setRange(int $min, int $max): self
    {
        $this->rangeMin = $min;
        $this->rangeMax = $max;

        return $this;
    }

    /**
     * @param int $rangeMin
     *
     * @return $this
     */
    public function setRangeMin(int $rangeMin): self
    {
        $this->rangeMin = $rangeMin;

        return $this;
    }

    /**
     * @param int $rangeMax
     *
     * @return $this
     */
    public function setRangeMax(int $rangeMax): self
    {
        $this->rangeMax = $rangeMax;

        return $this;
    }

    /**
     * @param int[] $value
     *
     * @return $this
     */
    public function setSpecific(array $value): self
    {
        $this->specific = $value;

        return $this;
    }

    /**
     * @param int|string $value
     *
     * @return $this
     */
    public function addSpecific($value): self
    {
        $this->specific[] = $value === '*' ? $value : (int)$value;
        $this->specific   = array_unique($this->specific);

        return $this;
    }

    /**
     * @param int $interval
     *
     * @return $this
     */
    public function repeatsOn(int $interval): self
    {
        $this->repeats = $interval;

        return $this;
    }

    /**
     * @return int
     */
    public function getRepeats(): ?int
    {
        return $this->repeats;
    }

    /**
     * @return array
     */
    public function getSpecific(): array
    {
        return $this->specific;
    }

    /**
     * @return int
     */
    public function getRangeMin(): ?int
    {
        return $this->rangeMin;
    }

    /**
     * @return int
     */
    public function getRangeMax(): ?int
    {
        return $this->rangeMax;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
