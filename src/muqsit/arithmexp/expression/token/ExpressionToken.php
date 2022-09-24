<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use muqsit\arithmexp\expression\Expression;
use Stringable;

interface ExpressionToken extends Stringable{

	/**
	 * @param Expression $expression
	 * @param array<string, int|float> $variables
	 * @return int|float
	 */
	public function getValue(Expression $expression, array $variables) : int|float;
}