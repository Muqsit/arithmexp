<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use InvalidArgumentException;
use muqsit\arithmexp\operator\binary\assignment\LeftBinaryOperatorAssignment;
use muqsit\arithmexp\operator\binary\assignment\RightBinaryOperatorAssignment;
use muqsit\arithmexp\operator\binary\SimpleBinaryOperator;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase{

	private Parser $parser;

	protected function setUp() : void{
		$this->parser = Parser::createDefault();
	}

	public function testBinaryOperatorsOfSamePrecedence() : void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Cannot process binary operators of the same precedence but with different assignment types");
		$registry = $this->parser->getBinaryOperatorRegistry();
		$registry->register(new SimpleBinaryOperator("~", "BO1", 128, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x + $y, false, true));
		$registry->register(new SimpleBinaryOperator("\$", "BO2", 128, RightBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x + $y, false, true));
	}

	public function testEmptyString() : void{
		TestUtil::assertParserThrows($this->parser, "", ParseException::ERR_EXPR_EMPTY, 0, 0);
	}

	public function testEmptyStringWithParentheses() : void{
		TestUtil::assertParserThrows($this->parser, "( )", ParseException::ERR_EXPR_EMPTY, 0, 3);
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
		$this->parser->getFunctionRegistry()->register("fn", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + fn(2 3) * y", ParseException::ERR_UNEXPECTED_TOKEN, 9, 10);
	}

	public function testBadFunctionCallWithMissingRequiredTrailingArguments() : void{
		$this->parser->getFunctionRegistry()->register("fn", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + fn(2, ) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 11);
	}

	public function testBadFunctionCallWithMissingRequiredLeadingArguments() : void{
		$this->parser->getFunctionRegistry()->register("fn", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + fn(, 2) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 11);
	}

	public function testBadFunctionCallWithArgumentUnderflow() : void{
		TestUtil::assertParserThrows($this->parser, "x + fdiv(1) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 11);
	}

	public function testBadFunctionCallWithArgumentOverflow() : void{
		$this->parser->getFunctionRegistry()->register("fn", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + fn(3, 2, 1) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 15);
	}

	public function testArgumentSeparatorOutsideFunctionCall() : void{
		TestUtil::assertParserThrows($this->parser, "2 + 3 * (4, 5) / 6", ParseException::ERR_UNEXPECTED_TOKEN, 10, 11);
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