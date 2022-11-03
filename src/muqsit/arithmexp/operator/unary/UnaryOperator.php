<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use Closure;
use muqsit\arithmexp\function\FunctionFlags;

interface UnaryOperator{

	public function getSymbol() : string;

	public function getPrecedence() : int;

	public function getName() : string;

	/**
	 * @return Closure(int|float) : (int|float)
	 */
	public function getOperator() : Closure;

	/**
	 * @return int-mask-of<FunctionFlags::*>
	 */
	public function getFlags() : int;
}