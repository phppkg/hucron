<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/hucron
 * @license  MIT
 */

namespace HuCron;

use function array_search;
use function array_values;
use function count;
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
class Parser extends Token
{
    public const EVERY_NEXT_TYPES = [self::T_INTERVAL, self::T_FIELD, self::T_DAYOFWEEK, self::T_ONAT, self::T_WEEKDAYWEEKEND];

    public const DAYOFWEEK_PREV_TYPES = [self::T_ONAT, self::T_INTERVAL, self::T_EVERY, self::T_DAYOFWEEK];

    /**
     * @var string
     */
    private $statement = '';

    /**
     * @var array[]
     */
    private $rawTokens = [];

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
     * @return static
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Parse a string into a CRON expression
     *
     * @param string $value
     *
     * @return string
     */
    public function parse(string $value): string
    {
        $this->reset();
        $this->statement = $value;

        $this->tokens = $this->lex($value);
        $this->evaluate();

        return (string)$this->cron;
    }

    /**
     * Reset parser token position and CRON expression
     */
    public function reset(): void
    {
        $this->cron = new Cron();

        $this->statement = '';
        $this->position  = 0;
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
        $fmtString = strtolower(trim($string));

        // support quick parse shortcuts statement
        if (isset(self::SHORTCUTS[$fmtString])) {
            $this->cron = Cron::new(self::SHORTCUTS[$fmtString]);
            return [];
        }

        // do lex parse
        $regex  = $this->compileRegex();
        $tokens = [];
        $values = array_values($this->tokenMap);

        $fragment = strtok($fmtString, $delimiter);
        while (false !== $fragment) {
            if (preg_match($regex, $fragment, $matches)) {
                foreach ($matches as $offset => $val) {
                    // fix: cannot use empty for assert '0'
                    if ($val !== '' && $offset > 0) {
                        $token = $values[$offset - 1];
                        $value = $matches[0];

                        // resolve alias field.
                        // if ($token === self::T_FIELD) {
                        //     $value = Keywords::resolveField($value);
                        // }

                        $tokens[] = [
                            'token' => $token,
                            'value' => $value
                        ];
                    }
                }
            }

            // fetch next node.
            $fragment = strtok($delimiter);
        }

        $this->rawTokens = $tokens;

        // if first is not T_EVERY, do sort tokens.
        // $tokenNumber = count($tokens);
        // if ($tokenNumber > 2 && $tokens[0]['token'] !== self::T_EVERY) {
        //     $sortedTokens = $this->sortTokens($tokens);
        // } else {
        //     $sortedTokens = $tokens;
        // }
        $sortedTokens = $tokens;

        // vdump($sortedTokens, $tokens);
        foreach ($sortedTokens as $idx => &$item) {
            $nextIdx = $idx + 1;
            $nextVal = $sortedTokens[$nextIdx]['value'] ?? '';

            // will auto fix: '10 am' to '10:00 am'
            if (is_numeric($item['value']) && in_array($nextVal, ['am', 'pm'], true)) {
                $item['token'] = self::T_EXACTTIME;
                $item['value'] .= ':00';
            }
        }

        return $sortedTokens;
    }

    protected function sortTokens(array $tokens): array
    {
        $sortedTokens = [];
        foreach (self::SORTED_TOKENS as $token) {
            foreach ($tokens as $idx => $item) {
                if ($item['token'] === $token) {
                    $sortedTokens[] = $item;
                    unset($tokens[$idx]);
                }
            }
        }
        return $sortedTokens;
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
            case self::T_EVERY: // 'every|daily|weekly|monthly'
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
                $this->cron->dayOfWeek->setSpecific(Keywords::WEEKDAY_WEEKEND[$value]);
                $this->nilTime($this->cron->dayOfWeek);
                break;
            case self::T_DAYOFWEEK:
                // $this->expects($this->previous(), ['T_ONAT', 'T_INTERVAL', 'T_EVERY', 'T_DAYOFWEEK']);
                $this->expects($this->previous(), self::DAYOFWEEK_PREV_TYPES);
                $this->cron->dayOfWeek->addSpecific(Keywords::DAY_OF_WEEK[$value]);

                $this->nilTime($this->cron->dayOfWeek);
                break;
            case self::T_TO:
                $this->expects($this->next(), self::T_INTERVAL);
                $this->expects($this->previous(), self::T_INTERVAL);
                break;
            case self::T_TIMEOFDAY:
                $prev = $this->previous();

                // special check.
                // - current is 'midnight'. eg: 'every day midnight'
                // if ($prev['token'] !== self::T_FIELD || Keywords::resolveField($prev['value']) !== Field::DAY_OF_MONTH) {
                //     // eg: 'Daily at 10:00 am'. 'Every day at midnight'
                //     $this->expects($prev, [self::T_ONAT]);
                // }
                // old:
                $this->expects($prev, [self::T_ONAT]);

                $this->cron->hour->setSpecific([Keywords::TIME_OF_DAY[$value]]);
                $this->nilTime($this->cron->hour);
                break;
            case self::T_MONTH:
                $this->expects($this->previous(), [self::T_ONAT, self::T_IN]);
                $this->cron->month->addSpecific(Keywords::MONTH[$value]);

                $this->nilTime($this->cron->month);
                break;
            case self::T_FIELD:
                $this->expects($prev = $this->previous(), [self::T_INTERVAL, self::T_EVERY]);

                if (isset(Keywords::FIELD_ALIAS[$value])) {
                    if ($this->is($prev, self::T_INTERVAL)) { // eg: 'every 5 min'
                        $value = Keywords::FIELD_ALIAS[$value];
                    } else {
                        break; // eg: 'every day 10:00 am'
                    }
                }

                $field = $this->cron->{$value};

                if ($this->is($prev2 = $this->previous(2), self::T_TO)) {
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
                        // $method = $this->is($this->previous(2), self::T_EVERY) ? 'repeatsOn' : 'addSpecific';
                        $method = $this->is($prev2, self::T_EVERY) ? 'repeatsOn' : 'addSpecific';

                        $amt = Keywords::INTERVAL[$previous] ?? (int)$previous;
                    }

                    /** @see Field::addSpecific() */
                    /** @see Field::repeatsOn() */
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
     * @param array        $token {token:string, value:string}
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
            // $t = $token['token'] ?? self::T_UNKNOWN;
            $s = $this->statement;
            throw new ParseException("Expected $typStr but got $tknStr. (current: $curStr, st: '$s')");
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
    public function getStatement(): string
    {
        return $this->statement;
    }

    /**
     * @return array[]
     */
    public function getRawTokens(): array
    {
        return $this->rawTokens;
    }
}
