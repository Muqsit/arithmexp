<?php

declare(strict_types=1);

use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase{

	private Parser $parser;

	private function getParser() : Parser{
		return $this->parser ??= Parser::createDefault();
	}

	private function assertParseResultEquals(string $expression, int $code, int $start_pos, int $end_pos) : void{
		try{
			$this->getParser()->parse($expression);
		}catch(ParseException $e){
			self::assertEquals($code, $e->getCode());
			self::assertEquals($start_pos, $e->getStartPos());
			self::assertEquals($end_pos, $e->getEndPos());
		}
	}

	public function testEmptyString() : void{
		$this->assertParseResultEquals("", ParseException::ERR_EXPR_EMPTY, 0, 0);
	}

	public function testEmptyStringWithParentheses() : void{
		$this->assertParseResultEquals("( )", ParseException::ERR_EXPR_EMPTY, 0, 3);
	}

	public function testSecludedNumericLiteral() : void{
		$this->assertParseResultEquals("x + 2 3 ** y", ParseException::ERR_UNEXPECTED_TOKEN, 6, 7);
	}

	public function testSecludedFunctionCall() : void{
		$this->assertParseResultEquals("tan(x) + tan(y) tan(z) ** tan(w)", ParseException::ERR_UNEXPECTED_TOKEN, 16, 22);
	}

	public function testNoClosingParenthesis() : void{
		$this->assertParseResultEquals("x + (2 * y + (z / 2) + 5", ParseException::ERR_NO_CLOSING_PAREN, 4, 5);
	}

	public function testNoOpeningParenthesis() : void{
		$this->assertParseResultEquals("x + (2 * y + z / 2)) + 5", ParseException::ERR_NO_OPENING_PAREN, 18, 19);
	}

	public function testNoUnaryOperand() : void{
		$this->assertParseResultEquals("x*+", ParseException::ERR_NO_OPERAND_UNARY, 2, 3);
	}

	public function testNoBinaryLeftOperand() : void{
		$this->assertParseResultEquals("()*2", ParseException::ERR_NO_OPERAND_BINARY_LEFT, 2, 3);
	}

	public function testNoBinaryRightOperand() : void{
		$this->assertParseResultEquals("2*", ParseException::ERR_NO_OPERAND_BINARY_RIGHT, 1, 2);
	}

	public function testBadFunctionCallToUndefinedFunction() : void{
		$this->assertParseResultEquals("x * noFunc() / 3", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 12);
	}

	public function testBadFunctionCallWithMalformedArgumentList() : void{
		$this->getParser()->getFunctionRegistry()->register("malformedArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->assertParseResultEquals("x + malformedArgFnTest(2 3) * y", ParseException::ERR_UNEXPECTED_TOKEN, 25, 26);
	}

	public function testBadFunctionCallWithMissingRequiredTrailingArguments() : void{
		$this->getParser()->getFunctionRegistry()->register("missingTrailingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->assertParseResultEquals("x + missingTrailingArgFnTest(2, ) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 33);
	}

	public function testBadFunctionCallWithMissingRequiredLeadingArguments() : void{
		$this->getParser()->getFunctionRegistry()->register("missingLeadingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->assertParseResultEquals("x + missingLeadingArgFnTest(, 2) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 32);
	}

	public function testBadFunctionCallWithArgumentOverflow() : void{
		$this->getParser()->getFunctionRegistry()->register("argOverflowFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->assertParseResultEquals("x + argOverflowFnTest(3, 2, 1) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 30);
	}

	public function testArgumentSeparatorOutsideFunctionCall() : void{
		$this->assertParseResultEquals("2 + 3 * (4, 5) / 6", ParseException::ERR_UNEXPECTED_TOKEN, 10, 11);
	}
}