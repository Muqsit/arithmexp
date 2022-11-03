<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use Closure;
use muqsit\arithmexp\function\FunctionFlags;

final class SimpleUnaryOperator implements UnaryOperator{

	/**
	 * @param string $symbol
	 * @param string $name
	 * @param int $precedence
	 * @param Closure(int|float) : (int|float) $operator
	 * @param int-mask-of<FunctionFlags::*> $flags
	 */
	public function __construct(
		private string $symbol,
		private string $name,
		private int $precedence,
		private Closure $operator,
		private int $flags = 0
	){}

	public function getSymbol() : string{
		return $this->symbol;
	}

	public function getPrecedence() : int{
		return $this->precedence;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getOperator() : Closure{
		return $this->operator;
	}

	public function getFlags() : int{
		return $this->flags;
	}
}