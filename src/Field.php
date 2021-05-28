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
 * Represents a field within a CRON expression
 *
 * Class Field
 *
 * @package HuCron
 */
class Field
{
    /**
     * @var int
     */
    protected $repeats;

    /**
     * @var array
     */
    protected $specific = [];

    /**
     * @var int
     */
    protected $rangeMin;

    /**
     * @var int
     */
    protected $rangeMax;

    /**
     * Build CRON expression part based on set values
     *
     * @return string
     */
    public function __toString()
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

        if (!is_null($this->rangeMin) && !is_null($this->rangeMax)
            && $this->rangeMin >= 0 && $this->rangeMax >= 0
        ) {
            $value = $this->rangeMin . '-' . $this->rangeMax;
        }

        if ($value === '') {
            $value = '*';
        }

        return $value;
    }

    /**
     * @param $val
     */
    public function fromCronValue($val): void
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
     * @param $min
     * @param $max
     *
     * @return $this
     */
    public function setRange($min, $max): self
    {
        $this->rangeMin = $min;
        $this->rangeMax = $max;

        return $this;
    }

    /**
     * @param $rangeMin
     *
     * @return $this
     */
    public function setRangeMin($rangeMin): self
    {
        $this->rangeMin = $rangeMin;

        return $this;
    }

    /**
     * @param $rangeMax
     *
     * @return $this
     */
    public function setRangeMax($rangeMax): self
    {
        $this->rangeMax = $rangeMax;

        return $this;
    }

    /**
     * @param array $value
     *
     * @return $this
     */
    public function setSpecific(array $value): self
    {
        $this->specific = $value;

        return $this;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function addSpecific($value): self
    {
        $this->specific[] = $value;
        $this->specific   = array_unique($this->specific);

        return $this;
    }

    /**
     * @param $interval
     *
     * @return $this
     */
    public function repeatsOn($interval): self
    {
        $this->repeats = (int)$interval;

        return $this;
    }

    /**
     * @return int
     */
    public function getRepeats(): int
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
    public function getRangeMin(): int
    {
        return $this->rangeMin;
    }

    /**
     * @return int
     */
    public function getRangeMax(): int
    {
        return $this->rangeMax;
    }
}
