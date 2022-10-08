<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase{

	private Parser $parser;

	protected function setUp() : void{
		$this->parser = Parser::createDefault();
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

	public function testNoOpeningParenthesis() : void{
		TestUtil::assertParserThrows($this->parser, "x + (2 * y + z / 2)) + 5", ParseException::ERR_NO_OPENING_PAREN, 18, 19);
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
		TestUtil::assertParserThrows($this->parser, "x * noFunc() / 3", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 12);
	}

	public function testBadFunctionCallWithMalformedArgumentList() : void{
		$this->parser->getFunctionRegistry()->register("malformedArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + malformedArgFnTest(2 3) * y", ParseException::ERR_UNEXPECTED_TOKEN, 25, 26);
	}

	public function testBadFunctionCallWithMissingRequiredTrailingArguments() : void{
		$this->parser->getFunctionRegistry()->register("missingTrailingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + missingTrailingArgFnTest(2, ) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 33);
	}

	public function testBadFunctionCallWithMissingRequiredLeadingArguments() : void{
		$this->parser->getFunctionRegistry()->register("missingLeadingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + missingLeadingArgFnTest(, 2) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 32);
	}

	public function testBadFunctionCallWithArgumentUnderflow() : void{
		TestUtil::assertParserThrows($this->parser, "x + fdiv(1) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 11);
	}

	public function testBadFunctionCallWithArgumentOverflow() : void{
		$this->parser->getFunctionRegistry()->register("argOverflowFnTest", static fn(int $x, int $y) : int => $x + $y);
		TestUtil::assertParserThrows($this->parser, "x + argOverflowFnTest(3, 2, 1) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 30);
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
}