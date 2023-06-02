<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use muqsit\arithmexp\token\Token;
use function array_key_last;
use function strlen;

final class TokenBuilderState{

	public static function fromExpression(string $expression) : self{
		return new self($expression, [], 0, strlen($expression), 0);
	}

	/**
	 * @param string $expression
	 * @param list<Token> $captured_tokens
	 * @param int $offset
	 * @param int $length
	 * @param int $unknown_token_seq
	 */
	public function __construct(
		readonly public string $expression,
		public array $captured_tokens,
		public int $offset,
		readonly public int $length,
		public int $unknown_token_seq
	){}

	public function getLastCapturedToken() : ?Token{
		$index = array_key_last($this->captured_tokens);
		return $index !== null ? $this->captured_tokens[$index] : null;
	}
}