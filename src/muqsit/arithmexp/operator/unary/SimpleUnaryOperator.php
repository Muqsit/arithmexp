<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use Closure;

final class SimpleUnaryOperator implements UnaryOperator{

	/**
	 * @param string $symbol
	 * @param string $name
	 * @param Closure(int|float) : (int|float) $operator
	 * @param bool $deterministic
	 */
	public function __construct(
		private string $symbol,
		private string $name,
		private Closure $operator,
		private bool $deterministic = false
	){}

	public function getSymbol() : string{
		return $this->symbol;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getOperator() : Closure{
		return $this->operator;
	}

	public function isDeterministic() : bool{
		return $this->deterministic;
	}
}