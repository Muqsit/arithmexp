<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\Position;

final class FunctionCallArgumentSeparatorToken extends SimpleToken{

	public function __construct(Position $position){
		parent::__construct(TokenType::FUNCTION_CALL_ARGUMENT_SEPARATOR(), $position);
	}

	public function repositioned(Position $position) : self{
		return new self($position);
	}

	public function toExpressionToken(Parser $parser, string $expression) : ExpressionToken{
		throw ParseException::unexpectedToken($expression, $this);
	}

	public function jsonSerialize() : string{
		return ",";
	}
}