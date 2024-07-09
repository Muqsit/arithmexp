<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

interface OperatorPrecedence{

	public const EXPONENTIAL = 0;
	public const UNARY_NEGATIVE_POSITIVE = 1;
	public const UNARY_NOT = 2;
	public const MULTIPLICATION_DIVISION_MODULO = 3;
	public const ADDITION_SUBTRACTION = 4;
	public const COMPARISON_GREATER_LESSER = 5;
	public const COMPARISON_EQUALITY = 6;
	public const AND_SYMBOL = 7;
	public const OR_SYMBOL = 8;
	public const AND_TEXTUAL = 9;
	public const XOR = 10;
	public const OR_TEXTUAL = 11;
}