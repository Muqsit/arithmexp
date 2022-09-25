<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class AdditionBinaryOperator extends SimpleBinaryOperator{

	public static function createDefault() : self{
		return new self("+", "Addition", BinaryOperatorPrecedence::ADDITION_SUBTRACTION, BinaryOperatorAssignmentType::LEFT);
	}

	public function operate(float|int $x, float|int $y) : int|float{
		return $x + $y;
	}
}