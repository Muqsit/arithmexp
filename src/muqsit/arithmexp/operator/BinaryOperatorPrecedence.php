<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

interface BinaryOperatorPrecedence{

	public const EXPONENTIAL = 0;
	public const MULTIPLICATION_DIVISION_MODULO = 1;
	public const ADDITION_SUBTRACTION = 2;
}