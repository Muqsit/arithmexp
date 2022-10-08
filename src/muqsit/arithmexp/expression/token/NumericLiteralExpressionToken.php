<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\Position;

final class NumericLiteralExpressionToken implements ExpressionToken{

	public function __construct(
		public Position $position,
		public int|float $value
	){}

	public function getPos() : Position{
		return $this->position;
	}

	public function isDeterministic() : bool{
		return true;
	}

	public function getValue(Expression $expression, array $variables) : int|float{
		return $this->value;
	}

	public function equals(ExpressionToken $other) : bool{
		return $other instanceof self && $other->value === $this->value;
	}

	public function __toString() : string{
		return (string) $this->value;
	}
}