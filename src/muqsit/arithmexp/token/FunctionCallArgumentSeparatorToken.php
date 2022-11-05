<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

final class FunctionCallArgumentSeparatorToken extends SimpleToken{

	public function __construct(Position $position){
		parent::__construct(TokenType::FUNCTION_CALL_ARGUMENT_SEPARATOR(), $position);
	}

	public function repositioned(Position $position) : self{
		return new self($position);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		throw ParseException::unexpectedToken($state->expression, $this);
	}

	public function jsonSerialize() : string{
		return ",";
	}
}