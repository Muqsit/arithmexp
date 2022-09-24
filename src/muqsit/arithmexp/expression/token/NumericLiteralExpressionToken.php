<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

final class NumericLiteralExpressionToken implements ExpressionToken{

	public function __construct(
		public int|float $value
	){}

	public function __toString() : string{
		return (string) $this->value;
	}
}