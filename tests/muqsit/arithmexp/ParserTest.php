<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ParserTest extends TestCase{

	private Parser $parser;

	protected function setUp() : void{
		$this->parser = Parser::createDefault();
	}

	private function assertParserThrows(string $expression, int $code, int $start_pos, int $end_pos) : void{
		try{
			$this->parser->parse($expression);
		}catch(ParseException $e){
			self::assertEquals($code, $e->getCode());
			self::assertEquals($start_pos, $e->getStartPos());
			self::assertEquals($end_pos, $e->getEndPos());
			return;
		}

		throw new RuntimeException("Expression \"{$expression}\" did not throw any exception (expected " . ParseException::class . "(code: {$code}, start_pos: {$start_pos}, end_pos: {$end_pos}))");
	}

	public function testEmptyString() : void{
		$this->assertParserThrows("", ParseException::ERR_EXPR_EMPTY, 0, 0);
	}

	public function testEmptyStringWithParentheses() : void{
		$this->assertParserThrows("( )", ParseException::ERR_EXPR_EMPTY, 0, 3);
	}

	public function testSecludedNumericLiteral() : void{
		$this->assertParserThrows("x + 2 3 ** y", ParseException::ERR_UNEXPECTED_TOKEN, 6, 7);
	}

	public function testSecludedFunctionCall() : void{
		$this->assertParserThrows("tan(x) + tan(y) tan(z) ** tan(w)", ParseException::ERR_UNEXPECTED_TOKEN, 16, 22);
	}

	public function testNoClosingParenthesis() : void{
		$this->assertParserThrows("x + (2 * y + (z / 2) + 5", ParseException::ERR_NO_CLOSING_PAREN, 4, 5);
	}

	public function testNoOpeningParenthesis() : void{
		$this->assertParserThrows("x + (2 * y + z / 2)) + 5", ParseException::ERR_NO_OPENING_PAREN, 18, 19);
	}

	public function testNoUnaryOperand() : void{
		$this->assertParserThrows("x*+", ParseException::ERR_NO_OPERAND_UNARY, 2, 3);
	}

	public function testNoBinaryLeftOperand() : void{
		$this->assertParserThrows("()*2", ParseException::ERR_NO_OPERAND_BINARY_LEFT, 2, 3);
	}

	public function testNoBinaryRightOperand() : void{
		$this->assertParserThrows("2*", ParseException::ERR_NO_OPERAND_BINARY_RIGHT, 1, 2);
	}

	public function testBadFunctionCallToUndefinedFunction() : void{
		$this->assertParserThrows("x * noFunc() / 3", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 12);
	}

	public function testBadFunctionCallWithMalformedArgumentList() : void{
		$this->parser->getFunctionRegistry()->register("malformedArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->assertParserThrows("x + malformedArgFnTest(2 3) * y", ParseException::ERR_UNEXPECTED_TOKEN, 25, 26);
	}

	public function testBadFunctionCallWithMissingRequiredTrailingArguments() : void{
		$this->parser->getFunctionRegistry()->register("missingTrailingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->assertParserThrows("x + missingTrailingArgFnTest(2, ) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 33);
	}

	public function testBadFunctionCallWithMissingRequiredLeadingArguments() : void{
		$this->parser->getFunctionRegistry()->register("missingLeadingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->assertParserThrows("x + missingLeadingArgFnTest(, 2) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 32);
	}

	public function testBadFunctionCallWithArgumentUnderflow() : void{
		$this->assertParserThrows("x + fdiv(1) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 11);
	}

	public function testBadFunctionCallWithArgumentOverflow() : void{
		$this->parser->getFunctionRegistry()->register("argOverflowFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->assertParserThrows("x + argOverflowFnTest(3, 2, 1) * y", ParseException::ERR_UNRESOLVABLE_FCALL, 4, 30);
	}

	public function testArgumentSeparatorOutsideFunctionCall() : void{
		$this->assertParserThrows("2 + 3 * (4, 5) / 6", ParseException::ERR_UNEXPECTED_TOKEN, 10, 11);
	}
}