<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

use muqsit\arithmexp\operator\assignment\OperatorAssignment;
use muqsit\arithmexp\operator\binary\BinaryOperator;
use muqsit\arithmexp\operator\unary\UnaryOperator;

final class OperatorList{

	/**
	 * @param OperatorAssignment $assignment
	 * @param array<string, BinaryOperator> $binary
	 * @param array<string, UnaryOperator> $unary
	 */
	public function __construct(
		private OperatorAssignment $assignment,
		private array $binary,
		private array $unary
	){}

	public function getAssignment() : OperatorAssignment{
		return $this->assignment;
	}

	/**
	 * @return array<string, BinaryOperator>
	 */
	public function getBinary() : array{
		return $this->binary;
	}

	/**
	 * @return array<string, UnaryOperator>
	 */
	public function getUnary() : array{
		return $this->unary;
	}
}