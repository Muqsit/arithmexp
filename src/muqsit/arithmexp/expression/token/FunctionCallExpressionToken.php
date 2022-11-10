<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use Closure;
use muqsit\arithmexp\function\FunctionFlags;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\Token;

final class FunctionCallExpressionToken implements ExpressionToken{

	/**
	 * @param Position $position
	 * @param string $name
	 * @param int $argument_count
	 * @param Closure $function
	 * @param int-mask-of<FunctionFlags::*> $flags
	 * @param Token|null $parent
	 */
	public function __construct(
		public Position $position,
		public string $name,
		public int $argument_count,
		public Closure $function,
		public int $flags,
		public ?Token $parent = null
	){}

	public function getPos() : Position{
		return $this->position;
	}

	/**
	 * @return int-mask-of<FunctionFlags::*>
	 */
	public function getFlags() : int{
		return $this->flags;
	}

	public function isDeterministic() : bool{
		return ($this->flags & FunctionFlags::DETERMINISTIC) > 0;
	}

	public function equals(ExpressionToken $other) : bool{
		return $other instanceof self &&
			$other->name === $this->name &&
			$other->argument_count === $this->argument_count &&
			$other->function === $this->function &&
			$other->flags === $this->flags;
	}

	public function __toString() : string{
		return $this->name;
	}
}