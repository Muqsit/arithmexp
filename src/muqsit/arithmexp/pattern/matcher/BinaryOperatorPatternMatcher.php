<?php

declare(strict_types=1);

namespace muqsit\arithmexp\pattern\matcher;

use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\token\BinaryOperatorToken;
use function array_fill_keys;

final class BinaryOperatorPatternMatcher implements PatternMatcher{

	/**
	 * @param string[] $operators
	 * @return self
	 */
	public static function setOf(array $operators) : self{
		return new self(array_fill_keys($operators, true));
	}

	/**
	 * @param array<string, true> $entries
	 */
	private function __construct(
		private array $entries
	){}

	public function matches(ExpressionToken|array $entry) : bool{
		return $entry instanceof FunctionCallExpressionToken &&
			$entry->parent instanceof BinaryOperatorToken &&
			isset($this->entries[$entry->parent->getOperator()]);
	}
}