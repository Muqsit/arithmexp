<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class DivisionOperator implements Operator{

	public function __construct(){
	}

	public function getDefaultLeftValue() : ?float{
		return null;
	}

	public function getDefaultRightValue() : ?float{
		return null;
	}

	public function getValue(float $left, float $right) : float{
		return $left / $right;
	}
}