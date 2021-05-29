<?php /** @noinspection PhpUndefinedMethodInspection */
declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phpcom-lab/hucron
 * @license  MIT
 */

use HuCron\ParseException;
use HuCron\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Class ParserTest
 */
class ParserTest extends TestCase
{
    protected $parser;

    protected function getParser(): Parser
    {
        if ($this->parser === null) {
            $this->parser = new Parser();
        }

        $this->parser->reset();
        return $this->parser;
    }

    public function testParseException(): void
    {
        $this->expectException(ParseException::class);

        $parser = $this->getParser();
        $token = ['token' => Parser::T_EVERY];

        $parser->expects($token, Parser::T_ONAT);
    }

    public function testEvery(): void
    {
        $parser = $this->getParser();

        $statement = 'Every day at midnight';
        $this->assertEquals('0 0 * * *', $parser->parse($statement));
    }

    public function testExactTime(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('20 3 * * *', $parser->parse('Every day at 3:20'));

        $this->assertEquals('0 13 * * *', $parser->parse('Every day at 1p'));

        $this->assertEquals('0 14,15,5 * * *', $parser->parse('Every day at 2p, 3pm, and 5am'));
    }

    public function testMeridiem(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('0 15 * * *', $parser->parse('Every day at 3:00 PM'));
        $this->assertEquals('0 10 * * *', $parser->parse('Every day at 10:00 AM'));
    }

    public function testInterval(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('*/2 * * * *', $parser->parse('Every other minute'));

        $this->assertEquals('0 */3 * * *', $parser->parse('Every 3 hours'));
    }

    public function testField(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('0 0 * */3 *', $parser->parse('Every 3rd month'));

        $this->assertEquals('0 3-6 * * *', $parser->parse('Every 3 to 6 hours'));
    }

    public function testWeekday(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('0 0 * * 2', $parser->parse('Every tuesday'));
    }

    public function testTimeOfDay(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('0 12 * * *', $parser->parse('Every day at noon'));

        $this->assertEquals('0 0 * * *', $parser->parse('Every day at midnight'));
    }

    public function testOnAt(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('0 0 * * 0,6', $parser->parse('Every day on the weekend'));

        $this->assertEquals('0 0 * * 1,2,3,4,5', $parser->parse('Every day on a weekday'));
    }

    public function testIn(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('0 0 * 2 *', $parser->parse('Every day in February'));
    }

    public function testTo(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('5-12 * * * *', $parser->parse('Every 5 to 12 minutes'));
    }

    public function testMonth(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('0 * * 1 *', $parser->parse('Every hour in January'));
    }

    public function testWeekdayWeekend(): void
    {
        $parser = $this->getParser();

        $this->assertEquals('0 0 * * 0,6', $parser->parse('Every day on the weekend'));

        $this->assertEquals('0 0 * * 1,2,3,4,5', $parser->parse('Every weekday'));
    }

    public function testComprehensiveStrings(): void
    {
        $parser = $this->getParser();

        $this->assertEquals(
            '*/15 0 * * 0,6',
            $parser->parse('Every 15 minutes at midnight on the weekend')
        );

        $this->assertEquals(
            '*/2 12 * 8 1,2,3,4,5',
            $parser->parse('Every other minute in August at noon on the weekday')
        );

        $this->assertEquals('0 0 1 4 *', $parser->parse('The 1st day in April at midnight'));

        $this->assertEquals('25 14 * * 1,2,3,4,5', $parser->parse('Every day on the weekday at 2:25pm'));
    }

    public function testParseMore(): void
    {
        $parser = $this->getParser();
        $tests = [
            ['*/5 * * * *', 'every 5 min'],
            ['0 10 * * *', 'every day 10am'],
            ['0 10 * * *', 'Every day 10 am'],
            ['0 10 * * *', 'Every day 10:00 am'],
            ['20 10 * * *', 'every day 10:20 am'],
        ];

        foreach ($tests as [$want, $str]) {
            $this->assertEquals($want, $parser->parse($str));
        }
    }
}
