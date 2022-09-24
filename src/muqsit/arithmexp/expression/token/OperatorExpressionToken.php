<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

final class OperatorExpressionToken implements ExpressionToken{

	public function __construct(
		public string $operator
	){}

	public function __toString() : string{
		return $this->operator;
	}
}