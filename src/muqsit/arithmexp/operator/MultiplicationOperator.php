<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class MultiplicationOperator extends AdvancedOperator{

	public function getValue(float $left, float $right) : float{
		return $left * $right;
	}
}