<?php


namespace CronLingo;


class Field
{
    protected $repeats;

    protected $specific = [];

    protected $rangeMin;

    protected $rangeMax;


    public function __toString()
    {
        $value = '';
        if ($this->repeats) {
            $value = '*/' . $this->repeats;
        }

        if (count($this->specific) > 0) {
            if (strlen($value) > 0) {
                $value .= ',';
            }
            $value .= implode(',', $this->specific);
        }

        if (!is_null($this->rangeMin) && !is_null($this->rangeMax)
            && $this->rangeMin >= 0 && $this->rangeMax >= 0) {
            $value = intval($this->rangeMin).'-'.intval($this->rangeMax);
        }

        if (strlen($value) == 0) {
            $value = '*';
        }

        return $value;
    }

    public function isDirty()
    {
        return !is_null($this->repeats) || !is_null($this->rangeMin)
            || !is_null($this->rangeMax) || count($this->specific) > 0;
    }

    public function setRange($min, $max)
    {
        $this->rangeMin = $min;
        $this->rangeMax = $max;

        return $this;
    }

    public function setRangeMin($rangeMin)
    {
        $this->rangeMin = $rangeMin;

        return $this;
    }

    public function setRangeMax($rangeMax)
    {
        $this->rangeMax = $rangeMax;

        return $this;
    }

    public function setSpecific(array $value)
    {
        $this->specific = $value;

        return $this;
    }

    public function addSpecific($value)
    {
        $this->specific[] = $value;
        $this->specific = array_unique($this->specific);

        return $this;
    }

    public function repeatsOn($interval)
    {
        $this->repeats = intval($interval);

        return $this;
    }


}