<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use Closure;
use muqsit\arithmexp\expression\Expression;
use RuntimeException;

final class FunctionCallExpressionToken implements ExpressionToken{

	public function __construct(
		public string $name,
		public int $argument_count,
		public Closure $function
	){}

	public function getValue(Expression $expression, array $variables) : int|float{
		throw new RuntimeException("Don't know how to get value of " . self::class);
	}

	public function __toString() : string{
		return $this->name;
	}
}