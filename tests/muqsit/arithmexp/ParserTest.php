<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use InvalidArgumentException;
use muqsit\arithmexp\function\FunctionFlags;
use muqsit\arithmexp\function\SimpleFunctionInfo;
use muqsit\arithmexp\operator\assignment\LeftOperatorAssignment;
use muqsit\arithmexp\operator\assignment\RightOperatorAssignment;
use muqsit\arithmexp\operator\binary\SimpleBinaryOperator;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\token\UnaryOperatorToken;
use PHPUnit\Framework\TestCase;
use const M_PI;

final class ParserTest extends TestCase{

	private Parser $parser;
	private Parser $uo_parser;

	protected function setUp() : void{
		$this->parser = Parser::createDefault();
		$this->uo_parser = Parser::createUnoptimized();
	}

	public function testBinaryOperatorsAlongsideUnaryOperators() : void{
		$vars = ["y" => 6, "x" => 5, "c" => 4, "w" => 3];
		$expression = $this->uo_parser->parse("y + x + (c * -w) / 2");
		self::assertEquals($vars["y"] + $vars["x"] + ($vars["c"] * -$vars["w"]) / 2, $expression->evaluate($vars));
	}

	public function testBinaryOperatorsOfSamePrecedence() : void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Cannot process operators with same precedence (3) but different assignment types (0, 1)");
		$registry = $this->parser->getOperatorManager()->getBinaryRegistry();
		$registry->register(new SimpleBinaryOperator("~", "BO1", 128, LeftOperatorAssignment::instance(), SimpleFunctionInfo::from(static fn(int|float $x, int|float $y) : int|float => $x + $y, FunctionFlags::DETERMINISTIC)));
		$registry->register(new SimpleBinaryOperator("\$", "BO2", 128, RightOperatorAssignment::instance(), SimpleFunctionInfo::from(static fn(int|float $x, int|float $y) : int|float => $x + $y, FunctionFlags::DETERMINISTIC)));
	}

	public function testEmptyString() : void{
		TestUtil::assertParserThrows($this->parser, "", ParseException::ERR_EXPR_EMPTY, 0, 0);
	}

	public function testEmptyStringWithParentheses() : void{
		TestUtil::assertParserThrows($this->parser, "( )", ParseException::ERR_EXPR_EMPTY, 0, 3);
	}

	public function testNumericLiteralWithLeadingDot() : void{
		$expression = $this->parser->parse("x + .1 + y");
		self::assertEquals(0.1, $expression->evaluate(["x" => 0, "y" => 0]));
	}

	public function testMalformedDotNumericLiteral() : void{
		TestUtil::assertParserThrows($this->parser, ". + .", ParseException::ERR_UNEXPECTED_TOKEN, 0, 5);
	}

	public function testMalformedNumericLiteralWithTrailingDot() : void{
		TestUtil::assertParserThrows($this->parser, "x + 1. + y", ParseException::ERR_UNEXPECTED_TOKEN, 5, 10);
	}

	public function testSecludedNumericLiteral() : void{
		TestUtil::assertParserThrows($this->parser, "x + 2 3 ** y", ParseException::ERR_UNEXPECTED_TOKEN, 6, 7);
	}

	public function testSecludedFunctionCall() : void{
		TestUtil::assertParserThrows($this->parser, "tan(x) + tan(y) tan(z) ** tan(w)", ParseException::ERR_UNEXPECTED_TOKEN, 16, 22);
	}

	public function testNoClosingParenthesis() : void{
		TestUtil::assertParserThrows($this->parser, "x + (2 * y + (z / 2) + 5", ParseException::ERR_NO_CLOSING_PAREN, 4, 5);
	}

	public function testNoClosingParenthesisOfDifferingType() : void{
		TestUtil::assertParserThrows($this->parser, "x + [(2 * y + z / 2) + 5", ParseException::ERR_NO_CLOSING_PAREN, 4, 5);
	}

	public function testUnexpectedParenthesisOfOpeningType() : void{
		TestUtil::assertParserThrows($this->parser, "x + ([2 * y + z / 2) + 5", ParseException::ERR_UNEXPECTED_PAREN, 19, 20);
	}

	public function testNoOpeningParenthesis() : void{
		TestUtil::assertParserThrows($this->parser, "x + (2 * y + z / 2)) + 5", ParseException::ERR_NO_OPENING_PAREN, 19, 20);
	}

	public function testNoOpeningParenthesisOfDifferingType() : void{
		TestUtil::assertParserThrows($this->parser, "x + (2 * y + z / 2)] + 5", ParseException::ERR_NO_OPENING_PAREN, 19, 20);
	}

	public function testUnexpectedParenthesisOfClosingType() : void{
		TestUtil::assertParserThrows($this->parser, "x + (2 * y + z / 2]) + 5", ParseException::ERR_UNEXPECTED_PAREN, 18, 19);
	}

	public function testUnexpectedParenthesisTypeInOverlap() : void{
		TestUtil::assertParserThrows($this->parser, "x + (2 * y + z / [2)] + 5", ParseException::ERR_UNEXPECTED_PAREN, 19, 20);
	}

	public function testFunctionCallWithDifferingParenthesisTypes() : void{
		$this->parser->parse("tan(x)");
		TestUtil::assertParserThrows($this->parser, "tan[x]", ParseException::ERR_UNEXPECTED_TOKEN, 4, 5);
		TestUtil::assertParserThrows($this->parser, "tan{x}", ParseException::ERR_UNEXPECTED_TOKEN, 4, 5);
	}

	public function testFunctionLikeMacroArgumentParser() : void{
		$this->uo_parser->getFunctionRegistry()->registerMacro(
			"fn",
			static fn(int|float $x, int|float $y = 4, int|float $z = 16) : int|float => 0,
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array => null
		);

		$non_macro_parser = Parser::createUnoptimized();
		$non_macro_parser->getFunctionRegistry()->registerFunction("fn", static fn(int|float $x, int|float $y = 4, int|float $z = 16) : int|float => 0);

		TestUtil::assertParserThrows($this->uo_parser, "fn()", ParseException::ERR_UNRESOLVABLE_FCALL, 0, 4);
		TestUtil::assertExpressionsEqual($non_macro_parser->parse("fn(x)"), $this->uo_parser->parse("fn(x)"));
		TestUtil::assertExpressionsEqual($non_macro_parser->parse("fn(x, 4)"), $this->uo_parser->parse("fn(x,)"));
		TestUtil::assertExpressionsEqual($non_macro_parser->parse("fn(x, 4, 16)"), $this->uo_parser->parse("fn(x,,)"));
		TestUtil::assertExpressionsEqual($non_macro_parser->parse("fn(x, 4, pi)"), $this->uo_parser->parse("fn(x,,pi)"));
	}

	public function testBuiltInFunctionLikeMacros() : void{
		TestUtil::assertParserThrows($this->uo_parser, "max()", ParseException::ERR_UNRESOLVABLE_FCALL, 0, 5);
		TestUtil::assertExpressionsEqual($this->uo_parser->parse("max(x)"), $this->uo_parser->parse("x"));
		TestUtil::assertExpressionsEqual($this->uo_parser->parse("max(x, y)"), $this->uo_parser->parse("max(x, y)"));

		TestUtil::assertParserThrows($this->uo_parser, "min()", ParseException::ERR_UNRESOLVABLE_FCALL, 0, 5);
		TestUtil::assertExpressionsEqual($this->uo_parser->parse("min(x)"), $this->uo_parser->parse("x"));
		TestUtil::assertExpressionsEqual($this->uo_parser->parse("min(x, y)"), $this->uo_parser->parse("min(x, y)"));

		TestUtil::assertExpressionsEqual($this->uo_parser->parse("x ** y"), $this->uo_parser->parse("pow(x, y)"));
		TestUtil::assertExpressionsEqual($this->uo_parser->parse("x ** 0.5"), $this->uo_parser->parse("sqrt(x)"));
	}

	public function testFunctionLikeMacroArgumentReplacer() : void{
		$this->uo_parser->getFunctionRegistry()->registerMacro(
			"fn",
			static fn(int|float $x = 0, int|float $y = 4, int|float $z = 16) : int|float => 0,
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array => match(count($args)){
				0 => [new NumericLiteralToken($token->getPos(), M_PI)],
				1 => [
					$args[0],
					new UnaryOperatorToken($token->getPos(), "-")
				],
				2 => [
					$args[0],
					$args[1],
					new BinaryOperatorToken($token->getPos(), "*")
				],
				3 => [],
				default => null
			}
		);

		$non_macro_parser = Parser::createUnoptimized();
		$non_macro_parser->getFunctionRegistry()->registerFunction("fn", static fn(int|float $x = 0, int|float $y = 4, int|float $z = 16) : int|float => 0);

		TestUtil::assertExpressionsEqual($non_macro_parser->parse((string) M_PI), $this->uo_parser->parse("fn()"));
		TestUtil::assertExpressionsEqual($non_macro_parser->parse("-x"), $this->uo_parser->parse("fn(x)"));
		TestUtil::assertExpressionsEqual($non_macro_parser->parse("x * y"), $this->uo_parser->parse("fn(x, y)"));

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Macro must return a list of at least one element");
		$this->uo_parser->parse("fn(x, y, z)");
	}

	public function testNoUnaryOperand() : void{
		TestUtil::assertParserThrows($this->parser, "x*+", ParseException::ERR_NO_OPERAND_UNARY, 2, 3);
	}

	public function testNoBinaryLeftOperand() : void{
		TestUtil::assertParserThrows($this->parser, "()*2", ParseException::ERR_NO_OPERAND_BINARY_LEFT, 2, 3);
	}

	public function testNoBinaryRightOperand() : void{
		TestUtil::assertParserThrows($this->parser, "2*", ParseException::ERR_NO_OPERAND_BINARY_RIGHT, 1, 2);
	}

	public function testBadFunctionCallToUndefinedFunction() : void{
		TestUtil::assertParserThrows($this->parser, "x * fn() / 3", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 8);
	}

	public function testBadFunctionCallWithMalformedArgumentList() : void{
		$this->parser->getFunctionRegistry()->registerFunction("fn", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + fn(2 3) * y", ParseException::ERR_UNEXPECTED_TOKEN, 9, 10);
	}

	public function testBadFunctionCallWithMissingRequiredTrailingArguments() : void{
		$this->parser->getFunctionRegistry()->registerFunction("fn", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + fn(2, ) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 11);
	}

	public function testBadFunctionCallWithMissingRequiredLeadingArguments() : void{
		$this->parser->getFunctionRegistry()->registerFunction("fn", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + fn(, 2) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 11);
	}

	public function testBadFunctionCallWithArgumentUnderflow() : void{
		TestUtil::assertParserThrows($this->parser, "x + fdiv(1) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 11);
	}

	public function testBadFunctionCallWithArgumentOverflow() : void{
		$this->parser->getFunctionRegistry()->registerFunction("fn", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + fn(3, 2, 1) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 15);
	}

	public function testMtRandFunctionCall() : void{
		self::assertIsInt($this->parser->parse("mt_rand()")->evaluate());
		TestUtil::assertParserThrows($this->parser, "mt_rand(x)", ParseException::ERR_UNRESOLVABLE_FCALL, 0, 10);
		self::assertIsInt($this->parser->parse("mt_rand(1, 2)")->evaluate());
		TestUtil::assertParserThrows($this->parser, "mt_rand(x, y, z)", ParseException::ERR_UNRESOLVABLE_FCALL, 0, 16);
	}

	public function testRoundFunctionCallModeConstants() : void{
		self::assertEquals(["HALF_UP", "HALF_DOWN", "HALF_ODD", "HALF_EVEN"], [
			...$this->parser->parse("round(HALF_UP)")->findVariables(),
			...$this->parser->parse("round(HALF_DOWN)")->findVariables(),
			...$this->parser->parse("round(HALF_ODD)")->findVariables(),
			...$this->parser->parse("round(HALF_EVEN)")->findVariables()
		]);

		self::assertEquals(4.0, $this->parser->parse("round(3.5, 0, HALF_UP)")->evaluate());
		self::assertEquals(3.0, $this->parser->parse("round(3.5, 0, HALF_DOWN)")->evaluate());
		self::assertEquals(3.0, $this->parser->parse("round(3.5, 0, HALF_ODD)")->evaluate());
		self::assertEquals(4.0, $this->parser->parse("round(3.5, 0, HALF_EVEN)")->evaluate());
	}

	public function testArgumentSeparatorOutsideFunctionCall() : void{
		TestUtil::assertParserThrows($this->parser, "2 + 3 * (4, 5) / 6", ParseException::ERR_UNEXPECTED_TOKEN, 10, 11);
	}

	public function testDivisionByZeroBetweenZeroLiterals() : void{
		TestUtil::assertParserThrows($this->parser, "0 / 0", ParseException::ERR_UNRESOLVABLE_EXPRESSION, 4, 5);
	}

	public function testDivisionByZeroBetweenNumericLiterals() : void{
		TestUtil::assertParserThrows($this->parser, "1 / 0", ParseException::ERR_UNRESOLVABLE_EXPRESSION, 4, 5);
	}

	public function testDivisionByZeroBetweenNonNumericLiterals() : void{
		TestUtil::assertParserThrows($this->parser, "y / (x - x)", ParseException::ERR_UNRESOLVABLE_EXPRESSION, 5,10);
	}

	public function testModuloByZeroBetweenNumericLiterals() : void{
		TestUtil::assertParserThrows($this->parser, "1 % 0", ParseException::ERR_UNRESOLVABLE_EXPRESSION, 4, 5);
	}

	public function testModuloByZeroBetweenNonNumericLiterals() : void{
		TestUtil::assertParserThrows($this->parser, "y % (x - x)", ParseException::ERR_UNRESOLVABLE_EXPRESSION, 5,10);
	}
}