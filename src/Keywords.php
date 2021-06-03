<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

namespace HuCron;

/**
 * Class Keywords Dict
 *
 * @package HuCron
 */
class Keywords
{
    /**
     * month name list
     *
     * @var array
     */
    public const MONTH = [
        'january'   => 1,
        'february'  => 2,
        'march'     => 3,
        'april'     => 4,
        'may'       => 5,
        'june'      => 6,
        'july'      => 7,
        'august'    => 8,
        'september' => 9,
        'october'   => 10,
        'november'  => 11,
        'december'  => 12
    ];

    /**
     * day of week
     *
     * @var array
     */
    public const DAY_OF_WEEK = [
        'sun'       => 0, // short of sunday
        'sunday'    => 0,
        'mon'       => 1, // monday
        'monday'    => 1,
        'tuesday'   => 2,
        'wednesday' => 3,
        'thursday'  => 4,
        'fri'       => 5, // friday
        'friday'    => 5,
        'saturday'  => 6
    ];

    /**
     * @var array
     */
    public const WEEKDAY_WEEKEND = [
        'weekday' => [1, 2, 3, 4, 5],
        'weekend' => [0, 6]
    ];

    /**
     * @var array
     */
    public const TIME_OF_DAY = [
        'noon'     => 12,
        'midday'   => 12,
        'midnight' => 0
    ];

    /**
     * @var array
     */
    public const INTERVAL= [
        'second'  => 2,
        'third'   => 3,
        'fourth'  => 4,
        'fifth'   => 5,
        'sixth'   => 6,
        'seventh' => 7,
        'eighth'  => 8,
        'ninth'   => 9,
        'tenth'   => 10,
        'other'   => 2
    ];

    /**
     * There are some field alias map
     *
     * @var array
     */
    public const FIELD_ALIAS = [
        'day'     => Field::DAY_OF_MONTH,
        'days'    => Field::DAY_OF_MONTH,
        // alias for second
        'sec'     => Field::SECOND,
        'secs'    => Field::SECOND,
        'seconds' => Field::SECOND,
        // alias for minute
        'min'     => Field::MINUTE,
        'mins'    => Field::MINUTE,
        'minutes' => Field::MINUTE,
    ];

    /**
     * @param string $value
     *
     * @return string
     */
    public static function resolveField(string $value): string
    {
        return self::FIELD_ALIAS[$value] ?? $value;
    }
}
