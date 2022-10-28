<?php

declare(strict_types=1);

namespace muqsit\arithmexp\pattern\matcher;

use muqsit\arithmexp\expression\token\ExpressionToken;

final class NotPatternMatcher implements PatternMatcher{

	public function __construct(
		private PatternMatcher $matcher
	){}

	public function matches(ExpressionToken|array $entry) : bool{
		return !$this->matcher->matches($entry);
	}
}