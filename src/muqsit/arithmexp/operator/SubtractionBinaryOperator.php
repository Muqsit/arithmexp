<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class SubtractionBinaryOperator extends SimpleBinaryOperator{

	public static function createDefault() : self{
		return new self("-", "Subtraction", BinaryOperatorPrecedence::ADDITION_SUBTRACTION, BinaryOperatorAssignmentType::LEFT);
	}

	public function operate(int|float $x, int|float $y) : int|float{
		return $x - $y;
	}
}