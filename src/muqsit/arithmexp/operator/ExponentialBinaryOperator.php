<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class ExponentialBinaryOperator extends SimpleBinaryOperator{

	public static function createDefault() : self{
		return new self("**", "Exponential", BinaryOperatorPrecedence::EXPONENTIAL, BinaryOperatorAssignmentType::RIGHT);
	}

	public function operate(int|float $x, int|float $y) : int|float{
		return $x ** $y;
	}
}