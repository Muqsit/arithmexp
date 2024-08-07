<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use muqsit\arithmexp\Position;

final class BooleanLiteralExpressionToken implements ExpressionToken{

	public function __construct(
		public Position $position,
		public bool $value
	){}

	public function getPos() : Position{
		return $this->position;
	}

	public function isDeterministic() : bool{
		return true;
	}

	public function equals(ExpressionToken $other) : bool{
		return $other instanceof self && $other->value === $this->value;
	}

	public function __toString() : string{
		return $this->value ? "true" : "false";
	}
}