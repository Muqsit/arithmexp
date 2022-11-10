<?php

declare(strict_types=1);

namespace muqsit\arithmexp\pattern\matcher;

use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\OpcodeExpressionToken;
use muqsit\arithmexp\token\OpcodeToken;
use function array_fill_keys;

final class OpcodePatternMatcher implements PatternMatcher{

	/**
	 * @param list<OpcodeToken::OP_*> $codes
	 * @return self
	 */
	public static function setOf(array $codes) : self{
		return new self(array_fill_keys($codes, true));
	}

	/**
	 * @param array<OpcodeToken::OP_*, true> $entries
	 */
	private function __construct(
		private array $entries
	){}

	public function matches(ExpressionToken|array $entry) : bool{
		return $entry instanceof OpcodeExpressionToken && isset($this->entries[$entry->code]);
	}
}