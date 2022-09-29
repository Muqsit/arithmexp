<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

use Closure;
use muqsit\arithmexp\operator\assignment\BinaryOperatorAssignment;

interface BinaryOperator{

	public function getSymbol() : string;

	public function getName() : string;

	public function getPrecedence() : int;

	public function getAssignment() : BinaryOperatorAssignment;

	/**
	 * @return Closure(int|float, int|float) : (int|float)
	 */
	public function getOperator() : Closure;
}