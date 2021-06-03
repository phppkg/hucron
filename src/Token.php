<?php declare(strict_types=1);

namespace HuCron;

/**
 * Class Token
 *
 * @package HuCron
 */
class Token
{
    public const T_EVERY          = 'T_EVERY';
    public const T_EXACTTIME      = 'T_EXACTTIME'; // exec action time.
    public const T_MERIDIEM       = 'T_MERIDIEM'; // am/pm
    public const T_INTERVAL       = 'T_INTERVAL';
    public const T_FIELD          = 'T_FIELD';
    public const T_DAYOFWEEK      = 'T_DAYOFWEEK';
    public const T_TIMEOFDAY      = 'T_TIMEOFDAY';
    public const T_ONAT           = 'T_ONAT';
    public const T_IN             = 'T_IN';
    public const T_TO             = 'T_TO';
    public const T_MONTH          = 'T_MONTH';
    public const T_WEEKDAYWEEKEND = 'T_WEEKDAYWEEKEND';
    public const T_UNKNOWN        = 'T_UNKNOWN';

    /**
     * Regular expressions used to tokenize a string.
     * - token regex patterns
     *
     * @var array
     */
    protected $tokenMap = [
        'every|daily|weekly|monthly|yearly'                                                       => self::T_EVERY,
        '\d{1,2}:\d{2}(?:am|pm)?'                                                                 => self::T_EXACTTIME,
        '\d{1,2}(?:am|pm|a|p)'                                                                    => self::T_EXACTTIME,
        '(?:am|pm)'                                                                               => self::T_MERIDIEM,
        '\d+[st|th|rd|nd]?[^:]?|other|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth' => self::T_INTERVAL,
        'second|sec|secs|min|mins|minute|hour|day|days|month?'                                    => self::T_FIELD,
        'sunday|sun|monday|mon|tuesday|wednesday|thursday|friday|fri|saturday'                    => self::T_DAYOFWEEK,
        'noon|midday|midnight'                                                                    => self::T_TIMEOFDAY,
        'on|at'                                                                                   => self::T_ONAT,
        'in'                                                                                      => self::T_IN,
        'to'                                                                                      => self::T_TO,
        'january|february|march|april|may|june|july|august|september|october|november|december'   => self::T_MONTH,
        'weekend|weekday?'                                                                        => self::T_WEEKDAYWEEKEND,
    ];

    public const SORTED_TOKENS = [
        self::T_EVERY,
        self::T_FIELD,
        self::T_INTERVAL,
        self::T_EXACTTIME,
        self::T_ONAT,
        self::T_MERIDIEM,
        self::T_DAYOFWEEK,
        self::T_TIMEOFDAY,
        self::T_IN,
        self::T_TO,
        self::T_MONTH,
        self::T_WEEKDAYWEEKEND,
    ];

    // Shortcut statement
    public const SHORTCUTS = [
        '@yearly'   => '0 0 1 1 *',
        '@annually' => '0 0 1 1 *',
        '@monthly'  => '0 0 1 * *',
        '@weekly'   => '0 0 * * 0',
        '@daily'    => '0 0 * * *',
        '@hourly'   => '0 * * * *',
    ];

}
