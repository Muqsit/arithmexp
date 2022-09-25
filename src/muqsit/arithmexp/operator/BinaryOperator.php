<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

interface BinaryOperator{

	public function getSymbol() : string;

	public function getName() : string;

	public function getPrecedence() : int;

	/**
	 * @return BinaryOperatorAssignmentType::*
	 */
	public function getAssignmentType() : int;

	public function operate(int|float $x, int|float $y) : int|float;
}