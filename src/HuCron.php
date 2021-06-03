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
final class HuCron
{
    //----------------------------------------------------------
    // convert an statement/sentence/description to cron expression.
    //----------------------------------------------------------

    /**
     * @param $string
     *
     * @return string
     */
    public static function fromDescription($string): string
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
    public static function fromSentence($string): string
    {
        return Parser::new()->parse($string);
    }

    //----------------------------------------------------------
    // TODO convert an cron expression to statement/sentence/description.
    //----------------------------------------------------------

    /**
     * @param string $string
     *
     * @return string
     */
    public static function fromExpression(string $string): string
    {
        return Statement::fromCronString($string)->toStatement();
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function fromExpr(string $string): string
    {
        return Statement::fromCronString($string)->toStatement();
    }

    /**
     * @return Parser
     */
    public static function getParser(): Parser
    {
        return new Parser();
    }
}
