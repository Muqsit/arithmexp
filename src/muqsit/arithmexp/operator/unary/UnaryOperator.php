<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use Closure;

interface UnaryOperator{

	public function getSymbol() : string;

	public function getPrecedence() : int;

	public function getName() : string;

	/**
	 * @return Closure(int|float) : (int|float)
	 */
	public function getOperator() : Closure;

	public function isDeterministic() : bool;
}