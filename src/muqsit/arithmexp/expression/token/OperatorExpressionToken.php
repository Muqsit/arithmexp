<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\operator\BinaryOperator;
use RuntimeException;

final class OperatorExpressionToken implements ExpressionToken{

	public function __construct(
		public BinaryOperator $operator
	){}

	public function getValue(Expression $expression, array $variables) : int|float{
		throw new RuntimeException("Don't know how to get value of " . self::class);
	}

	public function __toString() : string{
		return $this->operator->getSymbol();
	}
}