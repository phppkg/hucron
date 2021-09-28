<?php declare(strict_types=1);
/**
 * This file is part of Kite.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/hucron
 * @license  MIT
 */

require 'vendor/autoload.php';

use HuCron\Parser;

$parser = new Parser();

array_shift($argv);


echo $parser->parse(implode(' ', $argv)) . PHP_EOL;
