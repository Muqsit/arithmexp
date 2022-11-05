<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\Parser;

final class ExpressionTokenBuilderState{

	/**
	 * @param Parser $parser
	 * @param string $expression
	 * @param ExpressionToken[] $tokens
	 */
	public function __construct(
		public Parser $parser,
		public string $expression,
		public array $tokens
	){}
}