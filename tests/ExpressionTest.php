<?php

declare(strict_types=1);

use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use PHPUnit\Framework\TestCase;

final class ExpressionTest extends TestCase{

	private Parser $parser;

	private function getParser() : Parser{
		return $this->parser ??= Parser::createDefault();
	}

	public function testEmptyString() : void{
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("Cannot parse empty expression \"\"");
		$this->getParser()->parse("");
	}

	public function testEmptyStringWithParentheses() : void{
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("Cannot parse empty expression \"( )\"");
		$this->getParser()->parse("( )");
	}

	public function testNoClosingParenthesis() : void{
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("No closing parenthesis specified for opening parenthesis at \"(\" (4:5) in \"x + (2 * y + (z / 2) + 5\"");
		$this->getParser()->parse("x + (2 * y + (z / 2) + 5");
	}

	public function testNoOpeningParenthesis() : void{
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("No opening parenthesis specified for closing parenthesis at \")\" (18:19) in \"x + (2 * y + z / 2)) + 5\"");
		$this->getParser()->parse("x + (2 * y + z / 2)) + 5");
	}

	public function testNoUnaryOperand() : void{
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("No right operand specified for unary operator at \"+\" (2:3) in \"x*+\"");
		$this->getParser()->parse("x*+");
	}

	public function testNoBinaryLeftOperand() : void{
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("No left operand specified for binary operator at \"*\" (2:3) in \"()*2\"");
		$this->getParser()->parse("()*2");
	}

	public function testNoBinaryRightOperand() : void{
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("No right operand specified for binary operator at \"*\" (1:2) in \"2*\"");
		$this->getParser()->parse("2*");
	}

	public function testBadFunctionCallToUndefinedFunction() : void{
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("Cannot resolve function call at \"noFunc\" (4:10) in \"x * noFunc() / 3\": Function \"noFunc\" is not registered");
		$this->getParser()->parse("x * noFunc() / 3");
	}

	public function testBadFunctionCallWithMalformedArgumentList() : void{
		$this->getParser()->getFunctionRegistry()->register("malformedArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("Unexpected Numeric Literal token encountered at \"3\" (25:26) in \"x + malformedArgFnTest(2 3) * y\"");
		$this->getParser()->parse("x + malformedArgFnTest(2 3) * y");
	}

	public function testBadFunctionCallWithMissingRequiredTrailingArguments() : void{
		$this->getParser()->getFunctionRegistry()->register("missingTrailingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("Cannot resolve function call at \"missingTrailingArgFnTest\" (4:28) in \"x + missingTrailingArgFnTest(2, ) * y\": Function \"missingTrailingArgFnTest\" does not have a default value for parameter #2");
		$this->getParser()->parse("x + missingTrailingArgFnTest(2, ) * y");
	}

	public function testBadFunctionCallWithMissingRequiredLeadingArguments() : void{
		$this->getParser()->getFunctionRegistry()->register("missingLeadingArgFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("Cannot resolve function call at \"missingLeadingArgFnTest\" (4:27) in \"x + missingLeadingArgFnTest(, 2) * y\": Function \"missingLeadingArgFnTest\" does not have a default value for parameter #1");
		$this->getParser()->parse("x + missingLeadingArgFnTest(, 2) * y");
	}

	public function testBadFunctionCallWithArgumentOverflow() : void{
		$this->getParser()->getFunctionRegistry()->register("argOverflowFnTest", static fn(int $x, int $y) : int => $x + $y);
		$this->expectException(ParseException::class);
		$this->expectExceptionMessage("Too many parameters supplied to function call at \"argOverflowFnTest\" (4:21) in \"x + argOverflowFnTest(3, 2, 1) * y\": Expected 2 parameters, got 3 parameters");
		$this->getParser()->parse("x + argOverflowFnTest(3, 2, 1) * y");
	}
}