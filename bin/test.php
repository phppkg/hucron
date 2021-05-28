<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @link     https://github.com/inhere
 * @author   https://github.com/inhere
 * @license  MIT
 */

require 'vendor/autoload.php';

use HuCron\Parser;

$parser = new Parser();

array_shift($argv);


echo $parser->parse(implode(' ', $argv)) . PHP_EOL;
