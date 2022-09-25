<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class MultiplicationBinaryOperator extends SimpleBinaryOperator{

	public static function createDefault() : self{
		return new self("*", "Multiplication", BinaryOperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, BinaryOperatorAssignmentType::LEFT);
	}

	public function operate(float|int $x, float|int $y) : int|float{
		return $x * $y;
	}
}