<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\Expression;

interface ExpressionOptimizer{

	public function run(Expression $expression) : Expression;
}