<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use Closure;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\Token;
use RuntimeException;

final class FunctionCallExpressionToken implements ExpressionToken{

	public function __construct(
		public Position $position,
		public string $name,
		public int $argument_count,
		public Closure $function,
		public bool $deterministic,
		public ?Token $parent = null
	){}

	public function getPos() : Position{
		return $this->position;
	}

	public function isDeterministic() : bool{
		return $this->deterministic;
	}

	public function getValue(Expression $expression, array $variables) : int|float{
		throw new RuntimeException("Don't know how to get value of " . self::class);
	}

	public function equals(ExpressionToken $other) : bool{
		return $other instanceof self &&
			$other->name === $this->name &&
			$other->argument_count === $this->argument_count &&
			$other->function === $this->function &&
			$other->deterministic === $this->deterministic;
	}

	public function __toString() : string{
		return $this->name;
	}
}