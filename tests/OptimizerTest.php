<?php

declare(strict_types=1);

use muqsit\arithmexp\expression\ConstantExpression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\Parser;
use PHPUnit\Framework\TestCase;

final class OptimizerTest extends TestCase{

	private Parser $parser;

	private function getParser() : Parser{
		return $this->parser ??= Parser::createDefault();
	}

	public function testConstantFoldingOptimization() : void{
		$expression = $this->getParser()->parse("37.28 * cos(37) + sin(85) * 22 / cos(73) + sin(91) + 84.47 * cos(68) + sin(29)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(37.28 * cos(37) + sin(85) * 22 / cos(73) + sin(91) + 84.47 * cos(68) + sin(29), $expression->evaluate());
	}

	public function testConstantPropagationOptimization() : void{
		$actual = $this->getParser()->parse("37.28 * cos(x) + sin(85) * 22 / cos(73) + sin(91) + 84.47 * cos(z) + sin(32)")->getPostfixExpressionTokens();
		$expected = $this->getParser()->parseRawExpression("37.28 * cos(x) + 5.2617521784192 + 0.10598751175116 + 84.47 * cos(z) + 0.55142668124169")->getPostfixExpressionTokens();

		$callback = static fn(ExpressionToken $token) : string => $token::class . "(" . $token . ")";
		self::assertEquals(array_map($callback, $actual), array_map($callback, $expected));
	}

	public function testNoOptimization() : void{
		$actual = $this->getParser()->parse("37.28 * cos(mt_rand(x, y) / 23.84) ** 3")->getPostfixExpressionTokens();
		$expected = $this->getParser()->parseRawExpression("37.28 * cos(mt_rand(x, y) / 23.84) ** 3")->getPostfixExpressionTokens();

		$callback = static fn(ExpressionToken $token) : string => $token::class . "(" . $token . ")";
		self::assertEquals(array_map($callback, $actual), array_map($callback, $expected));
	}
}