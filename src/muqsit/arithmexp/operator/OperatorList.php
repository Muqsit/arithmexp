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
		readonly public OperatorAssignment $assignment,
		readonly public array $binary,
		readonly public array $unary
	){}
}