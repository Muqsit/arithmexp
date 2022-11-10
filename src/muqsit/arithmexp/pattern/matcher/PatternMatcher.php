<?php

declare(strict_types=1);

namespace muqsit\arithmexp\pattern\matcher;

use muqsit\arithmexp\expression\token\ExpressionToken;

interface PatternMatcher{

	/**
	 * @param ExpressionToken|list<ExpressionToken> $entry
	 * @return bool
	 */
	public function matches(array|ExpressionToken $entry) : bool;
}