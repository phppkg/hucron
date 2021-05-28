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
    public static function fromExpr($string): string
    {
        return self::getParser()->parse($string);
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function fromStatement($string): string
    {
        return self::getParser()->parse($string);
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function fromExpression($string): string
    {
        return self::getParser()->parse($string);
    }

    /**
     * @return Parser
     */
    public static function getParser(): Parser
    {
        return new Parser();
    }
}
