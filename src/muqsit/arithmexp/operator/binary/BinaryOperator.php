<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary;

use Closure;
use muqsit\arithmexp\operator\assignment\OperatorAssignment;

interface BinaryOperator{

	public function getSymbol() : string;

	public function getName() : string;

	public function getPrecedence() : int;

	public function getAssignment() : OperatorAssignment;

	/**
	 * @return Closure(int|float, int|float) : (int|float)
	 */
	public function getOperator() : Closure;

	public function isCommutative() : bool;

	public function isDeterministic() : bool;
}