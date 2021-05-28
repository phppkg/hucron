<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

namespace HuCron;

/**
 * Factory interface for getting CRON expressions
 *
 * Class HuCron
 *
 * @package HuCron
 */
class HuCron
{
    /**
     * @param $string
     *
     * @return string
     */
    public static function fromExpr($string)
    {
        return self::getParser()->parse($string);
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function fromExpression($string)
    {
        return self::getParser()->parse($string);
    }

    /**
     * @return Parser
     */
    public static function getParser()
    {
        return new Parser();
    }
}
