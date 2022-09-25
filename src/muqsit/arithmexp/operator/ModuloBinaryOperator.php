<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class ModuloBinaryOperator extends SimpleBinaryOperator{

	public static function createDefault() : self{
		return new self("%", "Modulo", BinaryOperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, BinaryOperatorAssignmentType::LEFT);
	}

	public function operate(int|float $x, int|float $y) : int|float{
		return $x % $y;
	}
}