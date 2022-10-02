<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary;

use Closure;
use muqsit\arithmexp\operator\binary\assignment\BinaryOperatorAssignment;

final class SimpleBinaryOperator implements BinaryOperator{

	/**
	 * @param string $symbol
	 * @param string $name
	 * @param int $precedence
	 * @param BinaryOperatorAssignment $assignment_type
	 * @param Closure(int|float, int|float) : (int|float) $operator
	 * @param bool $deterministic
	 */
	public function __construct(
		private string $symbol,
		private string $name,
		private int $precedence,
		private BinaryOperatorAssignment $assignment_type,
		private Closure $operator,
		private bool $deterministic = false
	){}

	public function getSymbol() : string{
		return $this->symbol;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getPrecedence() : int{
		return $this->precedence;
	}

	public function getAssignment() : BinaryOperatorAssignment{
		return $this->assignment_type;
	}

	public function getOperator() : Closure{
		return $this->operator;
	}

	public function isDeterministic() : bool{
		return $this->deterministic;
	}
}