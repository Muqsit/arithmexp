<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class SubtractionOperator implements Operator{

	public function __construct(){
	}

	public function getDefaultLeftValue() : ?float{
		return 0.0;
	}

	public function getDefaultRightValue() : ?float{
		return null;
	}

	public function getValue(float $left, float $right) : float{
		return $left - $right;
	}
}