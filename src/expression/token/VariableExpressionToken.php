<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

final class VariableExpressionToken implements ExpressionToken{

	public function __construct(
		public string $label
	){}

	public function __toString() : string{
		return $this->label;
	}
}