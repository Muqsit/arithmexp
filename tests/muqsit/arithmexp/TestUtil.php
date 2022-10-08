<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TestUtil{

	public static function assertParserThrows(Parser $parser, string $expression, int $code, int $start_pos, int $end_pos) : void{
		try{
			$parser->parse($expression);
		}catch(ParseException $e){
			TestCase::assertEquals($code, $e->getCode());
			TestCase::assertEquals($start_pos, $e->getPos()->getStart());
			TestCase::assertEquals($end_pos, $e->getPos()->getEnd());
			return;
		}

		throw new RuntimeException("Expression \"{$expression}\" did not throw any exception (expected " . ParseException::class . "(code: {$code}, start_pos: {$start_pos}, end_pos: {$end_pos}))");
	}
}