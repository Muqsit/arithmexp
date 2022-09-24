<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use muqsit\arithmexp\expression\Expression;

final class NumericLiteralExpressionToken implements ExpressionToken{

	public function __construct(
		public int|float $value
	){}

	public function getValue(Expression $expression, array $variables) : int|float{
		return $this->value;
	}

	public function __toString() : string{
		return (string) $this->value;
	}
}