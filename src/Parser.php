<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

namespace HuCron;

use function array_search;
use function array_values;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function preg_match;
use function strtok;
use function strtolower;
use function trim;

/**
 * Class Parser
 * - Tokenizes a string and parses into a CRON expression
 *
 * @package HuCron
 */
class Parser
{
    public const T_EVERY          = 'T_EVERY';
    public const T_EXACTTIME      = 'T_EXACTTIME'; // exec action time.
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
    public const T_UNKNOWN        = 'T_UNKNOWN';

    public const EVERY_NEXT_TYPES = [self::T_INTERVAL, self::T_FIELD, self::T_DAYOFWEEK, self::T_ONAT, self::T_WEEKDAYWEEKEND];

    public const DAYOFWEEK_PREV_TYPES = [self::T_ONAT, self::T_INTERVAL, self::T_EVERY, self::T_DAYOFWEEK];

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
        'sunday|sun|monday|mon|tuesday|wednesday|thursday|friday|fri|saturday'                    => self::T_DAYOFWEEK,
        'noon|midday|midnight'                                                                    => self::T_TIMEOFDAY,
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
        'midday'   => 12,
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
     * Lex a string into tokens
     *
     * @param string $string
     *
     * @return array
     */
    protected function lex(string $string): array
    {
        $delimiter = ' ';
        $fragment  = strtok(strtolower($string), $delimiter);

        $regex  = $this->compileRegex();
        $tokens = [];

        while (false !== $fragment) {
            if (preg_match($regex, $fragment, $matches)) {
                foreach ($matches as $offset => $val) {
                    // fix: cannot use empty for assert '0'
                    if ($val !== '' && $offset > 0) {
                        $token = array_values($this->tokenMap)[$offset - 1];

                        $tokens[] = [
                            'token' => $token,
                            // 'value' => strtolower($matches[0])
                            'value' => $matches[0]
                        ];
                    }
                }
            }

            $fragment = strtok($delimiter);
        }

        foreach ($tokens as $idx => &$item) {
            // $prevIdx = $idx - 1;
            $nextIdx = $idx + 1;

            // will auto fix: '10 am' to '10:00 am'
            $nextVal = $tokens[$nextIdx]['value'] ?? '';
            if (is_numeric($item['value']) && in_array($nextVal, ['am', 'pm'], true)) {
                $item['token'] = self::T_EXACTTIME;
                $item['value'] .= ':00';
            }
        }
        // unset($item);

        return $tokens;
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
                $this->expects($this->next(), self::EVERY_NEXT_TYPES);
                break;
            case self::T_INTERVAL:
                $this->expects($this->next(), [self::T_FIELD, self::T_TO]);
                break;
            case self::T_EXACTTIME:
                $meridiem = '';
                if ($this->is($next = $this->next(), [self::T_MERIDIEM])) {
                    // $meridiem = $this->next()['value'];
                    $meridiem = $next['value'];
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
                // $this->expects($this->previous(), ['T_ONAT', 'T_INTERVAL', 'T_EVERY', 'T_DAYOFWEEK']);
                $this->expects($this->previous(), self::DAYOFWEEK_PREV_TYPES);
                $this->cron->dayOfWeek->addSpecific($this->dayOfWeekMap[$value]);

                $this->nilTime($this->cron->dayOfWeek);
                break;
            case self::T_TO:
                $this->expects($this->next(), self::T_INTERVAL);
                $this->expects($this->previous(), self::T_INTERVAL);
                break;
            case self::T_TIMEOFDAY:
                $this->expects($this->previous(), [self::T_ONAT]);

                $this->cron->hour->setSpecific([$this->timeOfDayMap[$value]]);
                $this->nilTime($this->cron->hour);
                break;
            case self::T_MONTH:
                $this->expects($this->previous(), [self::T_ONAT, self::T_IN]);

                $this->cron->month->addSpecific($this->monthMap[$value]);

                $this->nilTime($this->cron->month);
                break;
            case self::T_FIELD:
                $this->expects($prev = $this->previous(), [self::T_INTERVAL, self::T_EVERY]);

                if (isset($this->fieldMap[$value])) {
                    // if ($this->is($this->previous(), self::T_INTERVAL)) {
                    if ($this->is($prev, self::T_INTERVAL)) {
                        $value = $this->fieldMap[$value];
                    } else {
                        break;
                    }
                }

                $field = $this->cron->{$value};

                if ($this->is($this->previous(2), self::T_TO)) {
                    $this->expects($prev3 = $this->previous(3), [self::T_INTERVAL]);

                    // Set Range
                    // $field->setRange($this->previous(3)['value'], $this->previous()['value']);
                    $field->setRange((int)$prev3['value'], (int)$prev['value']);
                    // } elseif ($this->is($this->previous(), [self::T_INTERVAL, self::T_EVERY])) {
                } elseif ($this->is($prev, [self::T_INTERVAL, self::T_EVERY])) {
                    $method = 'addSpecific';
                    // $previous = $this->previous()['value'];
                    $previous = $prev['value'];

                    // if ($this->is($this->previous(), self::T_EVERY)) {
                    if ($this->is($prev, self::T_EVERY)) {
                        $amt = '*';
                    } else {
                        $method = $this->is($this->previous(2), self::T_EVERY) ? 'repeatsOn' : 'addSpecific';

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
     * Check if a token is of a type
     *
     * @param array        $token
     * @param string|array $types
     *
     * @return bool
     */
    protected function is(array $token, $types): bool
    {
        if ($token) {
            if (!is_array($types)) {
                return $token['token'] === $types;
            }

            return in_array($token['token'], $types, true);
        }

        return false;
    }

    /**
     * Enforce expectations of a certain token
     *
     * @param array|bool   $token
     * @param array|string $types
     */
    public function expects($token, $types): void
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        if (!$this->is($token, $types)) {
            $tknStr = $this->token2string($token);
            $curStr = $this->token2string($this->current());
            $typStr = implode(',', $types);

            // $t = $token['token'] ?? 'NULL';
            $t = $token['token'] ?? self::T_UNKNOWN;
            throw new ParseException("Expected $typStr but got $t. (current: $curStr, expects: $tknStr)");
        }
    }

    /**
     * @param array $token
     *
     * @return string
     */
    protected function token2string(array $token): string
    {
        $t = $token['token'] ?? self::T_UNKNOWN;
        $v = $token['value'] ?? 'NULL';

        return "$t:'$v'";
    }

    /**
     * Retrieve current token based on position
     *
     * @return array
     */
    protected function current(): array
    {
        return $this->tokens[$this->position] ?? [];
    }

    /**
     * Look ahead in the token array
     *
     * @param int $skip
     *
     * @return array
     */
    protected function next(int $skip = 1): array
    {
        return $this->seek($this->position + $skip);
    }

    /**
     * Look behind in the token array
     *
     * @param int $skip
     *
     * @return array
     */
    protected function previous(int $skip = 1): array
    {
        return $this->seek($this->position - $skip);
    }

    /**
     * Seek a specific token
     *
     * @param int $index
     *
     * @return array
     */
    protected function seek(int $index): array
    {
        return $this->tokens[$index] ?? [];
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
     * @return string
     */
    public function getRawExpr(): string
    {
        return $this->rawExpr;
    }
}
