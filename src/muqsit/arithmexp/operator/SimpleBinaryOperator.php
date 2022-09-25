<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

abstract class SimpleBinaryOperator implements BinaryOperator{

	/**
	 * @param string $symbol
	 * @param string $name
	 * @param int $precedence
	 * @param BinaryOperatorAssignmentType::* $assignment_type
	 */
	public function __construct(
		private string $symbol,
		private string $name,
		private int $precedence,
		private int $assignment_type
	){}

	final public function getSymbol() : string{
		return $this->symbol;
	}

	final public function getName() : string{
		return $this->name;
	}

	final public function getPrecedence() : int{
		return $this->precedence;
	}

	final public function getAssignmentType() : int{
		return $this->assignment_type;
	}
}