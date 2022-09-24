<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class RightParenthesisToken extends SimpleToken{

	public function __construct(int $start_pos, int $end_pos){
		parent::__construct(TokenType::PARENTHESIS_RIGHT(), $start_pos, $end_pos);
	}

	public function jsonSerialize() : string{
		return ")";
	}
}