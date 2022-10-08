<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;

final class FunctionCallArgumentSeparatorToken extends SimpleToken{

	public function __construct(Position $position){
		parent::__construct(TokenType::FUNCTION_CALL_ARGUMENT_SEPARATOR(), $position);
	}

	public function jsonSerialize() : string{
		return ",";
	}
}