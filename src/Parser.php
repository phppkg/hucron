<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

namespace HuCron;

use function array_values;
use function preg_match;
use function strtok;
use function strtolower;
use function trim;
use function vdump;

/**
 * Tokenizes a string and parses into a CRON expression
 *
 * Class Parser
 *
 * @package HuCron
 */
class Parser
{
    public const T_EVERY          = 'T_EVERY';
    public const T_EXACTTIME      = 'T_EXACTTIME';
    public const T_MERIDIEM       = 'T_MERIDIEM';
    public const T_INTERVAL       = 'T_INTERVAL';
    public const T_FIELD          = 'T_FIELD';
    public const T_DAYOFWEEK      = 'T_DAYOFWEEK';
    public const T_TIMEOFDAY      = 'T_TIMEOFDAY';
    public const T_ONAT           = 'T_ONAT';
    public const T_IN             = 'T_IN';
    public const T_TO             = 'T_TO';
    public const T_MONTH          = 'T_MONTH';
    public const T_WEEKDAYWEEKEND = 'T_WEEKDAYWEEKEND';

    /**
     * Regular expressions used to tokenize a string
     *
     * @var array
     */
    protected $tokenMap = [
        'every|daily|weekly|monthly'                                                              => self::T_EVERY,
        '\d{1,2}:\d{2}(?:am|pm)?'                                                                 => self::T_EXACTTIME,
        '\d{1,2}(?:am|pm|a|p)'                                                                    => self::T_EXACTTIME,
        '(?:am|pm)'                                                                               => self::T_MERIDIEM,
        '\d+[st|th|rd|nd]?[^:]?|other|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth' => self::T_INTERVAL,
        'second|sec|secs|min|mins|minute|hour|day|days|month?'                                    => self::T_FIELD,
        'sunday|sun|monday|mon|tuesday|wednesday|thursday|friday|fri|saturday'                        => self::T_DAYOFWEEK,
        'noon|midnight'                                                                           => self::T_TIMEOFDAY,
        'on|at'                                                                                   => self::T_ONAT,
        'in'                                                                                      => self::T_IN,
        'to'                                                                                      => self::T_TO,
        'january|february|march|april|may|june|july|august|september|october|november|december'   => self::T_MONTH,
        'weekend|weekday?'                                                                        => self::T_WEEKDAYWEEKEND,
    ];

    /**
     * alias map
     *
     * @var array
     */
    protected $fieldMap = [
        'day'     => 'dayOfMonth',
        'days'    => 'dayOfMonth',
        // alias for second
        'sec'     => 'second',
        'secs'    => 'second',
        'seconds' => 'second',
        // alias for minute
        'min'     => 'minute',
        'mins'    => 'minute',
        'minutes' => 'minute',
    ];

    /**
     * @var array
     */
    protected $dayOfWeekMap = [
        'sunday'    => 0,
        'sun'       => 0, // short of sunday
        'monday'    => 1,
        'mon'       => 1, // monday
        'tuesday'   => 2,
        'wednesday' => 3,
        'thursday'  => 4,
        'friday'    => 5,
        'fri'       => 5, // friday
        'saturday'  => 6
    ];

    /**
     * @var array
     */
    protected $monthMap = [
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
     * @var array
     */
    protected $intervalMap = [
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
     * @var array
     */
    protected $timeOfDayMap = [
        'noon'     => 12,
        'midnight' => 0
    ];

    /**
     * @var array
     */
    protected $weekdayWeekendMap = [
        'weekday' => [1, 2, 3, 4, 5],
        'weekend' => [0, 6]
    ];

    /**
     * @var string
     */
    private $rawExpr = '';

    /**
     * @var int
     */
    protected $position;

    /**
     * @var Cron
     */
    protected $cron;

    /**
     * Array of lexed tokens
     *
     * @var array
     */
    protected $tokens = [];

    /**
     * Parse a string into a CRON expression
     *
     * @param string $value
     *
     * @return string
     */
    public function parse(string $value): string
    {
        $this->rawExpr = $value;
        $this->tokens  = $this->lex($value);

        $this->reset();
        $this->evaluate();

        return (string)$this->cron;
    }

    /**
     * Reset parser token position and CRON expression
     */
    public function reset(): void
    {
        $this->cron = new Cron();

        $this->rawExpr  = '';
        $this->position = 0;
    }

    /**
     * For simple expressions, zero out the time so the cron
     * matches user expectation and does not execute constantly.
     *
     * E.g., someone would not expect "Every day on Tuesday"
     * to run for every minute and hour on Tuesday.
     *
     * @param $field
     */
    protected function nilTime($field): void
    {
        $order = array_search($field, $this->cron->ordered(), true);

        if ($order > 1 && !$this->cron->hour->isDirty()) {
            $this->cron->hour->addSpecific(0);
        }

        if ($order > 0 && !$this->cron->minute->isDirty()) {
            $this->cron->minute->addSpecific(0);
        }
    }

    /**
     * Evaluate tokens and build CRON expression
     */
    protected function evaluate(): void
    {
        if ($this->position >= count($this->tokens)) {
            return; // Finished parsing
        }

        $token = $this->current()['token'];
        $value = $this->current()['value'];

        switch ($token) {
            case self::T_EVERY:
                $this->expects($this->next(), ['T_INTERVAL', 'T_FIELD', 'T_DAYOFWEEK', 'T_ONAT', 'T_WEEKDAYWEEKEND']);
                break;
            case self::T_INTERVAL:
                $this->expects($this->next(), ['T_FIELD', 'T_TO']);
                break;
            case self::T_EXACTTIME:
                $meridiem = '';
                if ($this->is($this->next(), [self::T_MERIDIEM])) {
                    $meridiem = $this->next()['value'];
                }

                $hours = $minutes = 0;
                // "1p" "1pm" "1:20"
                $parts = explode(':', $value);
                if (isset($parts[0])) {
                    $hours = $parts[0];
                }

                // eg "1:20"
                if (isset($parts[1])) {
                    $minutes = (int)$parts[1];
                }

                // eg "1p" "1pm"
                if ($meridiem === 'pm' || strpos($value, 'pm') || strpos($value, 'p') !== false) {
                    $hours = (int)trim($hours, 'pm');
                    $hours += 12;
                }

                if ($this->is($this->previous(), self::T_ONAT)) {
                    $this->cron->hour->setSpecific([(int)$hours]);
                    $this->cron->minute->setSpecific([$minutes]);
                } else {
                    $this->cron->hour->addSpecific((int)$hours);
                    $this->cron->minute->addSpecific($minutes);
                }

                break;
            case self::T_WEEKDAYWEEKEND:
                $this->expects($this->previous(), [self::T_ONAT, self::T_EVERY]);
                $this->cron->dayOfWeek->setSpecific($this->weekdayWeekendMap[$value]);
                $this->nilTime($this->cron->dayOfWeek);
                break;
            case self::T_DAYOFWEEK:
                $this->expects($this->previous(), ['T_ONAT', 'T_INTERVAL', 'T_EVERY', 'T_DAYOFWEEK']);
                $this->cron->dayOfWeek->addSpecific($this->dayOfWeekMap[$value]);

                $this->nilTime($this->cron->dayOfWeek);
                break;
            case self::T_TO:
                $this->expects($this->next(), 'T_INTERVAL');
                $this->expects($this->previous(), 'T_INTERVAL');
                break;
            case self::T_TIMEOFDAY:
                $this->expects($this->previous(), ['T_ONAT']);

                $this->cron->hour->setSpecific([$this->timeOfDayMap[$value]]);
                $this->nilTime($this->cron->hour);
                break;
            case self::T_MONTH:
                $this->expects($this->previous(), ['T_ONAT', 'T_IN']);

                $this->cron->month->addSpecific($this->monthMap[$value]);

                $this->nilTime($this->cron->month);
                break;
            case self::T_FIELD:
                $this->expects($this->previous(), ['T_INTERVAL', 'T_EVERY']);

                if (isset($this->fieldMap[$value])) {
                    if ($this->is($this->previous(), self::T_INTERVAL)) {
                        $value = $this->fieldMap[$value];
                    } else {
                        break;
                    }
                }

                $field = $this->cron->{$value};

                if ($this->is($this->previous(2), 'T_TO')) {
                    $this->expects($this->previous(3), ['T_INTERVAL']);
                    // Range
                    $field->setRange($this->previous(3)['value'], $this->previous()['value']);
                } elseif ($this->is($this->previous(), ['T_INTERVAL', 'T_EVERY'])) {
                    $previous = $this->previous()['value'];

                    if ($this->is($this->previous(), 'T_EVERY')) {
                        $method = 'addSpecific';

                        $amt = '*';
                    } else {
                        $method = $this->is($this->previous(2), 'T_EVERY') ? 'repeatsOn' : 'addSpecific';

                        $amt = $this->intervalMap[$previous] ?? (int)$previous;
                    }

                    $field->{$method}($amt);
                }

                $this->nilTime($field);
                break;
            default:
                break;
        }

        $this->position++;
        $this->evaluate();
    }

    /**
     * Check if a token is of a type
     *
     * @param array|bool   $token
     * @param string|array $types
     *
     * @return bool
     */
    protected function is($token, $types): bool
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        if (false !== $token) {
            return in_array($token['token'], $types, true);
        }

        return false;
    }

    /**
     * Enforce expectations of a certain token
     *
     * @param $token
     * @param $types
     */
    public function expects($token, $types): void
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        if (!$this->is($token, $types)) {
            $t = $token['token'] ?? 'NULL';
            throw new ParseException('Expected ' . implode(',', $types) . ' but got ' . $t);
        }
    }

    /**
     * Retrieve current token based on position
     *
     * @return array
     */
    protected function current(): array
    {
        return $this->tokens[$this->position];
    }

    /**
     * Look ahead in the token array
     *
     * @param int $skip
     *
     * @return array|bool
     */
    protected function next(int $skip = 1)
    {
        return $this->seek($this->position + $skip);
    }

    /**
     * Look behind in the token array
     *
     * @param int $skip
     *
     * @return array|bool
     */
    protected function previous(int $skip = 1)
    {
        return $this->seek($this->position - $skip);
    }

    /**
     * Seek a specific token
     *
     * @param $index
     *
     * @return array|bool
     */
    protected function seek($index)
    {
        return $this->tokens[$index] ?? false;
    }

    /**
     * Concatenate regex expressions into a single regex for performance
     *
     * @return string
     */
    protected function compileRegex(): string
    {
        return '~(' . implode(')|(', array_keys($this->tokenMap)) . ')~iA';
    }

    /**
     * Lex a string into tokens
     *
     * @param string $string
     *
     * @return array
     */
    protected function lex(string $string): array
    {
        $delimiter = ' ';
        $fragment  = strtok($string, $delimiter);

        $regex  = $this->compileRegex();
        $tokens = [];

        while (false !== $fragment) {
            if (preg_match($regex, $fragment, $matches)) {
                foreach ($matches as $offset => $val) {
                    if (!empty($val) && $offset > 0) {
                        $token = array_values($this->tokenMap)[$offset - 1];

                        $tokens[] = [
                            'token' => $token,
                            'value' => strtolower($matches[0])
                        ];
                    }
                }
            }
            $fragment = strtok($delimiter);
        }

        return $tokens;
    }

    /**
     * @return string
     */
    public function getRawExpr(): string
    {
        return $this->rawExpr;
    }
}
