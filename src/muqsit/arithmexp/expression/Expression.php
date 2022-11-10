<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use Generator;
use muqsit\arithmexp\expression\token\ExpressionToken;

interface Expression{

	public function getExpression() : string;

	/**
	 * @return list<ExpressionToken>
	 */
	public function getPostfixExpressionTokens() : array;

	/**
	 * @return Generator<string>
	 */
	public function findVariables() : Generator;

	/**
	 * @param array<string, int|float> $variable_values
	 * @return int|float
	 */
	public function evaluate(array $variable_values = []) : int|float;
}