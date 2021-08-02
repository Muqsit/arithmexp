<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

interface Operator{

	public function getValue(float $left, float $right) : float;
}