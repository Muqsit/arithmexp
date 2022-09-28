<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

use Closure;

interface BinaryOperator{

	public function getSymbol() : string;

	public function getName() : string;

	public function getPrecedence() : int;

	/**
	 * @return BinaryOperatorAssignmentType::*
	 */
	public function getAssignmentType() : int;

	/**
	 * @return Closure(int|float, int|float) : (int|float)
	 */
	public function getOperator() : Closure;
}