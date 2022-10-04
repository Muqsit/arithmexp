<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\Parser;

interface ExpressionOptimizer{

	public function run(Parser $parser, Expression $expression) : Expression;
}