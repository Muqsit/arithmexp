<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use InvalidArgumentException;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\Position;

final class VariableExpressionToken implements ExpressionToken{

	public function __construct(
		public Position $position,
		public string $label
	){}

	public function getPos() : Position{
		return $this->position;
	}

	public function isDeterministic() : bool{
		return false;
	}

	public function retrieveValue(Expression $expression, array $variables) : int|float{
		return $variables[$this->label] ?? throw new InvalidArgumentException("No value supplied for variable \"{$this->label}\" in \"{$expression->getExpression()}\"");
	}

	public function equals(ExpressionToken $other) : bool{
		return $other instanceof self && $other->label === $this->label;
	}

	public function __toString() : string{
		return $this->label;
	}
}