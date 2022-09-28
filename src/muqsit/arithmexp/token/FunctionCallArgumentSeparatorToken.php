<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class FunctionCallArgumentSeparatorToken extends SimpleToken{

	public function __construct(int $start_pos, int $end_pos){
		parent::__construct(TokenType::FUNCTION_CALL_ARGUMENT_SEPARATOR(), $start_pos, $end_pos);
	}

	public function jsonSerialize() : string{
		return ",";
	}
}