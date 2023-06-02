<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_map;

final class TestUtil{

	public static function assertExpressionsEqual(Expression $expected, Expression $actual) : void{
		$callback = static fn(ExpressionToken $token) : string => $token::class . "(" . $token . ")";
		TestCase::assertEquals(array_map($callback, $actual->getPostfixExpressionTokens()), array_map($callback, $expected->getPostfixExpressionTokens()));
	}

	public static function assertParserThrows(Parser $parser, string $expression, int $code, int $start_pos, int $end_pos) : void{
		try{
			$parser->parse($expression);
		}catch(ParseException $e){
			TestCase::assertEquals($code, $e->getCode());
			TestCase::assertEquals($start_pos, $e->position->getStart());
			TestCase::assertEquals($end_pos, $e->position->getEnd());
			return;
		}

		throw new RuntimeException("Expression \"{$expression}\" did not throw any exception (expected " . ParseException::class . "(code: {$code}, start_pos: {$start_pos}, end_pos: {$end_pos}))");
	}
}