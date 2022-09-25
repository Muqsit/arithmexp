<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class BinaryOperatorList{

	/**
	 * @param BinaryOperatorAssignmentType::* $assignment_type
	 * @param array<string, BinaryOperator> $operators
	 */
	public function __construct(
		private int $assignment_type,
		private array $operators
	){}

	/**
	 * @return BinaryOperatorAssignmentType::*
	 */
	public function getAssignmentType() : int{
		return $this->assignment_type;
	}

	/**
	 * @return array<string, BinaryOperator>
	 */
	public function getOperators() : array{
		return $this->operators;
	}
}