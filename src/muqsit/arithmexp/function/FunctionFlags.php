<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

interface FunctionFlags{

	public const DETERMINISTIC = 1 << 0;
	public const COMMUTATIVE = 1 << 1;
}