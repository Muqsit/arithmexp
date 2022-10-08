<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;

final class RightParenthesisToken extends SimpleToken{

	public function __construct(Position $position){
		parent::__construct(TokenType::PARENTHESIS_RIGHT(), $position);
	}

	public function jsonSerialize() : string{
		return ")";
	}
}