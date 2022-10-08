<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;

interface ExpressionOptimizer{

	/**
	 * @param Parser $parser
	 * @param Expression $expression
	 * @return Expression
	 * @throws ParseException
	 */
	public function run(Parser $parser, Expression $expression) : Expression;
}