<?php

declare(strict_types=1);

use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase{

	private static function assertExceptionEquals(ParseException $exception, int $code, int $start_pos, int $end_pos) : void{
		self::assertEquals($code, $exception->getCode());
		self::assertEquals($start_pos, $exception->getStartPos());
		self::assertEquals($end_pos, $exception->getEndPos());
	}

	private Parser $parser;

	private function getParser() : Parser{
		return $this->parser ??= Parser::createDefault();
	}

	public function testEmptyString() : void{
		try{
			$this->getParser()->parse("");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_EXPR_EMPTY, 0, 0);
		}
	}

	public function testEmptyStringWithParentheses() : void{
		try{
			$this->getParser()->parse("( )");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_EXPR_EMPTY, 0, 3);
		}
	}

	public function testSecludedNumericLiteral() : void{
		try{
			$this->getParser()->parse("x + 2 3 ** y");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_UNEXPECTED_TOKEN, 6, 7);
		}
	}

	public function testSecludedFunctionCall() : void{
		try{
			$this->getParser()->parse("tan(x) + tan(y) tan(z) ** tan(w)");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_UNEXPECTED_TOKEN, 16, 22);
		}
	}

	public function testNoClosingParenthesis() : void{
		try{
			$this->getParser()->parse("x + (2 * y + (z / 2) + 5");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_NO_CLOSING_PAREN, 4, 5);
		}
	}

	public function testNoOpeningParenthesis() : void{
		try{
			$this->getParser()->parse("x + (2 * y + z / 2)) + 5");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_NO_OPENING_PAREN, 18, 19);
		}
	}

	public function testNoUnaryOperand() : void{
		try{
			$this->getParser()->parse("x*+");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_NO_OPERAND_UNARY, 2, 3);
		}
	}

	public function testNoBinaryLeftOperand() : void{
		try{
			$this->getParser()->parse("()*2");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_NO_OPERAND_BINARY_LEFT, 2, 3);
		}
	}

	public function testNoBinaryRightOperand() : void{
		try{
			$this->getParser()->parse("2*");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_NO_OPERAND_BINARY_RIGHT, 1, 2);
		}
	}

	public function testBadFunctionCallToUndefinedFunction() : void{
		try{
			$this->getParser()->parse("x * noFunc() / 3");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_UNRESOLVABLE_FCALL, 4, 12);
		}
	}

	public function testBadFunctionCallWithMalformedArgumentList() : void{
		$this->getParser()->getFunctionRegistry()->register("malformedArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		try{
			$this->getParser()->parse("x + malformedArgFnTest(2 3) * y");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_UNEXPECTED_TOKEN, 25, 26);
		}
	}

	public function testBadFunctionCallWithMissingRequiredTrailingArguments() : void{
		$this->getParser()->getFunctionRegistry()->register("missingTrailingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		try{
			$this->getParser()->parse("x + missingTrailingArgFnTest(2, ) * y");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_UNRESOLVABLE_FCALL, 4, 33);
		}
	}

	public function testBadFunctionCallWithMissingRequiredLeadingArguments() : void{
		$this->getParser()->getFunctionRegistry()->register("missingLeadingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		try{
			$this->getParser()->parse("x + missingLeadingArgFnTest(, 2) * y");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_UNRESOLVABLE_FCALL, 4, 32);
		}
	}

	public function testBadFunctionCallWithArgumentOverflow() : void{
		$this->getParser()->getFunctionRegistry()->register("argOverflowFnTest", static fn(int $x, int $y) : int => $x + $y);
		try{
			$this->getParser()->parse("x + argOverflowFnTest(3, 2, 1) * y");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_UNRESOLVABLE_FCALL, 4, 30);
		}
	}

	public function testArgumentSeparatorOutsideFunctionCall() : void{
		try{
			$this->getParser()->parse("2 + 3 * (4, 5) / 6");
		}catch(ParseException $e){
			self::assertExceptionEquals($e, ParseException::ERR_UNEXPECTED_TOKEN, 10, 11);
		}
	}
}