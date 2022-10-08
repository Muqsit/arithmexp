<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;

final class LeftParenthesisToken extends SimpleToken{

	public function __construct(Position $position){
		parent::__construct(TokenType::PARENTHESIS_LEFT(), $position);
	}

	public function jsonSerialize() : string{
		return "(";
	}
}