<?php

declare(strict_types=1);

use muqsit\arithmexp\expression\ConstantExpression;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\Parser;
use PHPUnit\Framework\TestCase;

final class OptimizerTest extends TestCase{

	private static function assertExpressionsEqual(Expression $expected, Expression $actual) : void{
		$callback = static fn(ExpressionToken $token) : string => $token::class . "(" . $token . ")";
		self::assertEquals(array_map($callback, $actual->getPostfixExpressionTokens()), array_map($callback, $expected->getPostfixExpressionTokens()));
	}

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
		$actual = $this->getParser()->parse("37.28 * cos(x) + sin(85) * 22 / cos(73) + sin(91) + 84.47 * cos(z) + sin(32)");
		$expected = $this->getParser()->parseRawExpression("37.28 * cos(x) + 5.2617521784192 + 0.10598751175116 + 84.47 * cos(z) + 0.55142668124169");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testNoOptimization() : void{
		$actual = $this->getParser()->parse("37.28 * cos(mt_rand(x, y) / 23.84) ** 3");
		$expected = $this->getParser()->parseRawExpression("37.28 * cos(mt_rand(x, y) / 23.84) ** 3");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForLeftOperandZero() : void{
		$expression = $this->getParser()->parse("2 ** 0 + 3 * x ** (0 ** 7) + 6");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(2 ** 0 + 3 * mt_rand() ** (0 ** 7) + 6, $expression->evaluate());
	}

	public function testExponentOperatorStrengthReductionForRightOperandTwo() : void{
		$actual = $this->getParser()->parse("x ** 2 + x ** 3 + y ** (5 - 3)");
		$expected = $this->getParser()->parseRawExpression("(x * x) + (x ** 3) + (y * y)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForRightOperandOne() : void{
		$actual = $this->getParser()->parse("x ** 1 + x ** 3 + x ** (4 - 3)");
		$expected = $this->getParser()->parseRawExpression("x + (x ** 3) + x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForRightOperandZero() : void{
		$actual = $this->getParser()->parse("x ** 0 + x ** 3 + x ** (4 - 4)");
		$expected = $this->getParser()->parseRawExpression("1 + (x ** 3) + 1");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testMultiplicationOperatorStrengthReductionForLeftOperandOne() : void{
		$actual = $this->getParser()->parse("x * 1 + x * (7 / 7)");
		$expected = $this->getParser()->parseRawExpression("x + x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testMultiplicationOperatorStrengthReductionForRightOperandOne() : void{
		$actual = $this->getParser()->parse("1 * x + (7 / 7) * x");
		$expected = $this->getParser()->parseRawExpression("x + x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testMultiplicationOperatorStrengthReductionForOperandZero() : void{
		$actual = $this->getParser()->parse("x + y * 0 + z * (0 / 2)");
		$expected = $this->getParser()->parseRawExpression("x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testDivisionOperatorStrengthReductionForEqualOperands() : void{
		$actual = $this->getParser()->parse("x / x + y / y + z / z");
		$expected = $this->getParser()->parseRawExpression("3");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testDivisionOperatorStrengthReductionForLeftOperandZero() : void{
		$actual = $this->getParser()->parse("x + 0 / x + ((3 - 3) / y)");
		$expected = $this->getParser()->parseRawExpression("x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testDivisionOperatorStrengthReductionForRightOperandOne() : void{
		$actual = $this->getParser()->parse("x / 1 + y / 1");
		$expected = $this->getParser()->parseRawExpression("x + y");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testAdditionOperatorStrengthReductionForOperandZero() : void{
		$actual = $this->getParser()->parse("(x + 0) + (0 + y)");
		$expected = $this->getParser()->parseRawExpression("x + y");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testSubtractionOperatorStrengthReductionForOperandZero() : void{
		$actual = $this->getParser()->parse("(x - 0) - (0 - y)");
		$expected = $this->getParser()->parseRawExpression("x - y");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testSubtractionOperatorStrengthReductionForEqualOperands() : void{
		$actual = $this->getParser()->parse("x ** (x - x) - 1");
		$expected = $this->getParser()->parseRawExpression("0");
		self::assertExpressionsEqual($expected, $actual);
	}
}