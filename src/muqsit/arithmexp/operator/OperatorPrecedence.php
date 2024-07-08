<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

interface OperatorPrecedence{

	public const EXPONENTIAL = 0;
	public const UNARY_NEGATIVE_POSITIVE = 1;
	public const UNARY_NOT = 2;
	public const MULTIPLICATION_DIVISION_MODULO = 3;
	public const ADDITION_SUBTRACTION = 4;
}