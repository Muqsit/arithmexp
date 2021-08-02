<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class AdditionOperator implements Operator{

	public function __construct(){
	}

	public function getValue(float $left, float $right) : float{
		return $left + $right;
	}
}