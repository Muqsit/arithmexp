<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\Position;
use Stringable;

interface ExpressionToken extends Stringable{

	public function getPos() : Position;

	public function isDeterministic() : bool;

	/**
	 * @param Expression $expression
	 * @param array<string, int|float> $variables
	 * @return int|float
	 */
	public function getValue(Expression $expression, array $variables) : int|float;

	public function equals(self $other) : bool;
}