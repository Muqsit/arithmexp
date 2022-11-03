<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use Closure;
use muqsit\arithmexp\expression\ConstantExpression;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\operator\assignment\RightOperatorAssignment;
use muqsit\arithmexp\operator\binary\SimpleBinaryOperator;
use muqsit\arithmexp\operator\OperatorPrecedence;
use PHPUnit\Framework\TestCase;
use function array_map;

final class OptimizerTest extends TestCase{

	private static function assertExpressionsEqual(Expression $expected, Expression $actual) : void{
		$callback = static fn(ExpressionToken $token) : string => $token::class . "(" . $token . ")";
		self::assertEquals(array_map($callback, $actual->getPostfixExpressionTokens()), array_map($callback, $expected->getPostfixExpressionTokens()));
	}

	private Parser $parser;
	private Parser $unoptimized_parser;

	protected function setUp() : void{
		$this->parser = Parser::createDefault();
		$this->unoptimized_parser = Parser::createUnoptimized();
	}

	public function testConstantFoldingOptimization() : void{
		$expression = $this->parser->parse("37.28 * cos(37) + sin(85) * 22 / cos(73) + sin(91) + 84.47 * cos(68) + sin(29)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(37.28 * cos(37) + sin(85) * 22 / cos(73) + sin(91) + 84.47 * cos(68) + sin(29), $expression->evaluate());
	}

	public function testConstantFoldingOptimizationForNumericLiteral() : void{
		$expression = $this->parser->parse("1.23");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(1.23, $expression->evaluate());
	}

	public function testConstantFoldingOptimizationForNestedFunctionCall() : void{
		$expression = $this->parser->parse("min(pi())");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(min([pi()]), $expression->evaluate());
	}

	public function testConstantFoldingOptimizationForNestedFunctionCalls() : void{
		$expression = $this->parser->parse("min(pi() + pi() + pi() + pi())");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(min([pi() + pi() + pi() + pi()]), $expression->evaluate());
	}

	public function testConstantPropagationOptimization() : void{
		$actual = $this->parser->parse("37.28 * cos(x) + sin(85) * 22 / cos(73) + sin(91) + 84.47 * cos(z) + sin(32)");
		$expected = $this->unoptimized_parser->parse("(cos(x) * 37.28) + ((cos(z) * 84.47) + 5.919166371412)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testNoOptimization() : void{
		$actual = $this->parser->parse("37.28 * cos(mt_rand(x, y) / 23.84) ** 3");
		$expected = $this->unoptimized_parser->parse("cos(mt_rand(x, y) / 23.84) ** 3 * 37.28");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testNonDeterminsticFunctionCallExclusionFromOptimization() : void{
		$actual = $this->parser->parse("mt_rand(1, 2) - mt_rand(1, 2)");
		$expected = $this->unoptimized_parser->parse("mt_rand(1, 2) - mt_rand(1, 2)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testNonDeterminsticOperatorExclusionFromOptimization() : void{
		$operator = new SimpleBinaryOperator(":", "Random", OperatorPrecedence::EXPONENTIAL, RightOperatorAssignment::instance(), Closure::fromCallable("mt_rand"), false, false);

		$this->parser->getOperatorManager()->getBinaryRegistry()->register($operator);
		$actual = $this->parser->parse("1:4 / 1:4");

		$this->unoptimized_parser->getOperatorManager()->getBinaryRegistry()->register($operator);
		$expected = $this->unoptimized_parser->parse("1:4 / 1:4");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForLeftOperandZero() : void{
		$expression = $this->parser->parse("2 ** 0 + 3 * x ** (0 ** 7) + 6");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(2 ** 0 + 3 * mt_rand() ** (0 ** 7) + 6, $expression->evaluate());
	}

	public function testExponentOperatorStrengthReductionForLeftOperandZeroWithGrouping() : void{
		$expression = $this->parser->parse("0 ** (x + y + z)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(0, $expression->evaluate());
	}

	public function testExponentOperatorStrengthReductionForOperandTwo() : void{
		$expression = $this->parser->parse("2 ** 2");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(4, $expression->evaluate());
	}

	public function testExponentOperatorStrengthReductionForRightOperandTwo() : void{
		$actual = $this->parser->parse("x ** 2 + x ** 3 + y ** (5 - 3)");
		$expected = $this->unoptimized_parser->parse("(x * x) + (x ** 3 + y * y)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForRightOperandTwoWithGrouping() : void{
		$actual = $this->parser->parse("(x + y + z) ** 2");
		$expected = $this->unoptimized_parser->parse("(x + (y + z)) * (x + (y + z))");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForRightOperandOne() : void{
		$actual = $this->parser->parse("x ** 1 + x ** 3 + x ** (4 - 3)");
		$expected = $this->unoptimized_parser->parse("(x ** 3) + (x + x)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForRightOperandOneWithGrouping() : void{
		$actual = $this->parser->parse("(x + y + z) ** 1");
		$expected = $this->unoptimized_parser->parse("x + (y + z)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForRightOperandZero() : void{
		$actual = $this->parser->parse("x ** 0 + x ** 3 + x ** (4 - 4)");
		$expected = $this->unoptimized_parser->parse("(x ** 3) + 2");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForRightOperandZeroWithGrouping() : void{
		$expression = $this->parser->parse("(x + y + z) ** 0");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(1, $expression->evaluate());
	}

	public function testExponentOperatorStrengthReductionForLeftOperandOne() : void{
		$actual = $this->parser->parse("1 ** x + 3 ** x + (4 - 3) ** x");
		$expected = $this->unoptimized_parser->parse("(3 ** x) + 2");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testExponentOperatorStrengthReductionForLeftOperandOneWithGrouping() : void{
		$expression = $this->parser->parse("1 ** (x + y + z)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(1, $expression->evaluate());
	}

	public function testMultiplicationOperatorStrengthReductionForLeftOperandOne() : void{
		$actual = $this->parser->parse("x * 1 + x * (7 / 7)");
		$expected = $this->unoptimized_parser->parse("x + x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testMultiplicationOperatorStrengthReductionForLeftOperandOneWithGrouping() : void{
		$actual = $this->parser->parse("1 * (x + y + z)");
		$expected = $this->unoptimized_parser->parse("x + (y + z)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testMultiplicationOperatorStrengthReductionForRightOperandOne() : void{
		$actual = $this->parser->parse("1 * x + (7 / 7) * x");
		$expected = $this->unoptimized_parser->parse("x + x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testMultiplicationOperatorStrengthReductionForRightOperandOneWithGrouping() : void{
		$actual = $this->parser->parse("1 * (x + y + z)");
		$expected = $this->unoptimized_parser->parse("x + (y + z)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testMultiplicationOperatorStrengthReductionForOperandZero() : void{
		$actual = $this->parser->parse("x + y * 0 + z * (0 / 2)");
		$expected = $this->unoptimized_parser->parse("x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testMultiplicationOperatorStrengthReductionForOperandZeroWithGrouping() : void{
		$expression = $this->parser->parse("(x + y + z) * 0 + 0 * (x + y + z)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(0, $expression->evaluate());
	}

	public function testDivisionOperatorStrengthReductionForEqualOperands() : void{
		$expression = $this->parser->parse("x / x + y / y + z / z");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(3, $expression->evaluate());
	}

	public function testDivisionOperatorStrengthReductionForEqualOperandsWithGrouping() : void{
		$expression = $this->parser->parse("(x + y + z) / (x + y + z)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(1, $expression->evaluate());
	}

	public function testDivisionOperatorStrengthReductionForCommutativelyEqualOperands() : void{
		$expression = $this->parser->parse("(x + y + z) / (y + x + z)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(1, $expression->evaluate());
	}

	public function testDivisionOperatorStrengthReductionForLeftOperandZero() : void{
		$actual = $this->parser->parse("x + 0 / x + ((3 - 3) / y)");
		$expected = $this->unoptimized_parser->parse("x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testDivisionOperatorStrengthReductionForLeftOperandZeroWithGrouping() : void{
		$expression = $this->parser->parse("0 / (x + y + z)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(0, $expression->evaluate());
	}

	public function testDivisionOperatorStrengthReductionForRightOperandOne() : void{
		$actual = $this->parser->parse("x / 1 + y / 1");
		$expected = $this->unoptimized_parser->parse("x + y");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testDivisionOperatorStrengthReductionForRightOperandOneWithGrouping() : void{
		$actual = $this->parser->parse("(x + y + z) / 1");
		$expected = $this->unoptimized_parser->parse("x + (y + z)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testDivisionOperatorStrengthReductionForCommonSubExpressionsAmongOperands() : void{
		$actual = $this->parser->parse("(x * y) / (y * z)");
		$expected = $this->unoptimized_parser->parse("x / z");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testDivisionOperatorStrengthReductionForNumericSubExpressionsAmongOperands() : void{
		$actual = $this->parser->parse("(x * 2) / (4 * y)");
		$expected = $this->unoptimized_parser->parse("x * 0.5 / y");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testDivisionOperatorStrengthReductionForCommutativeFunctions() : void{
		$expression = $this->parser->parse("min(x, y) / min(y, x)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(1, $expression->evaluate());
	}

	public function testDivisionOperatorStrengthReductionForNonCommutativeFunctions() : void{
		$actual = $this->parser->parse("pow(x, y) / pow(y, x)");
		$expected = $this->unoptimized_parser->parse("pow(x, y) / pow(y, x)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testDivisionOperatorStrengthReductionForQuotientRuleOfExponents() : void{
		$actual = $this->parser->parse("x ** y / x ** z");
		$expected = $this->unoptimized_parser->parse("x ** (y - z)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testModuloOperatorStrengthReductionForRightOperandOne() : void{
		$expression = $this->parser->parse("y % (4 - 3)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(0, $expression->evaluate());
	}

	public function testModuloOperatorStrengthReductionForRightOperandOneWithGrouping() : void{
		$expression = $this->parser->parse("w % ((x * y) / (y * x))");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(0, $expression->evaluate());
	}

	public function testAdditionOperatorStrengthReductionForOperandZero() : void{
		$actual = $this->parser->parse("(x + 0) + (0 + y)");
		$expected = $this->unoptimized_parser->parse("x + y");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testAdditionOperatorStrengthReductionForOperandZeroWithGrouping() : void{
		$actual = $this->parser->parse("(x * y * z) + 0");
		$expected = $this->unoptimized_parser->parse("x * (y * z)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testAdditionOperatorStrengthReductionForNegativeLeftOperand() : void{
		$actual = $this->parser->parse("-x + y");
		$expected = $this->unoptimized_parser->parse("y - x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testAdditionOperatorStrengthReductionForNegativeRightOperand() : void{
		$actual = $this->parser->parse("x + -y");
		$expected = $this->unoptimized_parser->parse("x - y");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testAdditionOperatorStrengthReductionForNegativeOperands() : void{
		$actual = $this->parser->parse("-x + -y");

		$expected = $this->unoptimized_parser->parse("(x + y) * 1");
		$expected = new RawExpression($expected->getExpression(), array_map(
			static fn(ExpressionToken $token) : ExpressionToken => $token instanceof NumericLiteralExpressionToken && $token->value === 1 ? new NumericLiteralExpressionToken($token->getPos(), -1) : $token,
			$expected->getPostfixExpressionTokens()
		));

		self::assertExpressionsEqual($expected, $actual);
	}

	public function testSubtractionOperatorStrengthReductionForOperandZero() : void{
		$actual = $this->parser->parse("(x - 0) - (0 - y)");
		$expected = $this->unoptimized_parser->parse("x + y");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testSubtractionOperatorStrengthReductionForOperandZeroWithGrouping() : void{
		$actual = $this->parser->parse("(x * y * z) - 0");
		$expected = $this->unoptimized_parser->parse("x * (y * z)");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testSubtractionOperatorStrengthReductionForEqualOperands() : void{
		$expression = $this->parser->parse("x ** (x - x) - 1");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(0, $expression->evaluate());
	}

	public function testSubtractionOperatorStrengthReductionForEqualOperandsYieldingNan() : void{
		$expression = $this->parser->parse("inf - inf");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertNan($expression->evaluate());
	}

	public function testSubtractionOperatorStrengthReductionForEqualOperandsWithGrouping() : void{
		$expression = $this->parser->parse("(x * y * z) - (x * y * z)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(0, $expression->evaluate());
	}

	public function testSubtractionOperatorStrengthReductionForCommutativelyEqualOperands() : void{
		$expression = $this->parser->parse("(x * y) - (y * x)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(0, $expression->evaluate());
	}

	public function testSubtractionOperatorStrengthReductionForCommutativeFunctions() : void{
		$expression = $this->parser->parse("min(x, y) - min(y, x)");
		self::assertInstanceOf(ConstantExpression::class, $expression);
		self::assertEquals(0, $expression->evaluate());
	}

	public function testSubtractionOperatorStrengthReductionForNegativeLeftOperand() : void{
		$actual = $this->parser->parse("-x - y");

		$expected = $this->unoptimized_parser->parse("(x + y) * 1");
		$expected = new RawExpression($expected->getExpression(), array_map(
			static fn(ExpressionToken $token) : ExpressionToken => $token instanceof NumericLiteralExpressionToken && $token->value === 1 ? new NumericLiteralExpressionToken($token->getPos(), -1) : $token,
			$expected->getPostfixExpressionTokens()
		));

		self::assertExpressionsEqual($expected, $actual);
	}

	public function testSubtractionOperatorStrengthReductionForNegativeRightOperand() : void{
		$actual = $this->parser->parse("x - -y");
		$expected = $this->unoptimized_parser->parse("x + y");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testSubtractionOperatorStrengthReductionForNegativeOperands() : void{
		$actual = $this->parser->parse("-x - -y");

		$expected = $this->unoptimized_parser->parse("(x - y) * 1");
		$expected = new RawExpression($expected->getExpression(), array_map(
			static fn(ExpressionToken $token) : ExpressionToken => $token instanceof NumericLiteralExpressionToken && $token->value === 1 ? new NumericLiteralExpressionToken($token->getPos(), -1) : $token,
			$expected->getPostfixExpressionTokens()
		));

		self::assertExpressionsEqual($expected, $actual);
	}

	public function testPositiveOperatorStrengthReductionForCommutativeFunctions() : void{
		$actual = $this->parser->parse("+x");
		$expected = $this->unoptimized_parser->parse("x");
		self::assertExpressionsEqual($expected, $actual);
	}

	public function testNegativeOperatorStrengthReductionForCommutativeFunctions() : void{
		$actual = $this->parser->parse("---x");

		$expected = $this->unoptimized_parser->parse("x * 1");
		$expected = new RawExpression($expected->getExpression(), array_map(
			static fn(ExpressionToken $token) : ExpressionToken => $token instanceof NumericLiteralExpressionToken && $token->value === 1 ? new NumericLiteralExpressionToken($token->getPos(), -1) : $token,
			$expected->getPostfixExpressionTokens()
		));

		self::assertExpressionsEqual($expected, $actual);
	}
}