<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

use muqsit\arithmexp\operator\assignment\BinaryOperatorAssignment;

final class BinaryOperatorList{

	/**
	 * @param BinaryOperatorAssignment $assignment
	 * @param array<string, BinaryOperator> $operators
	 */
	public function __construct(
		private BinaryOperatorAssignment $assignment,
		private array $operators
	){}

	public function getAssignment() : BinaryOperatorAssignment{
		return $this->assignment;
	}

	/**
	 * @return array<string, BinaryOperator>
	 */
	public function getOperators() : array{
		return $this->operators;
	}
}