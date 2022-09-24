<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use muqsit\arithmexp\token\Token;
use function array_key_last;

final class TokenBuilderState{

	/**
	 * @param string $expression
	 * @param Token[] $captured_tokens
	 * @param int $offset
	 * @param int $length
	 * @param int $unknown_token_seq
	 */
	public function __construct(
		public string $expression,
		public array $captured_tokens,
		public int $offset,
		public int $length,
		public int $unknown_token_seq
	){}

	public function getLastCapturedToken() : ?Token{
		$index = array_key_last($this->captured_tokens);
		return $index !== null ? $this->captured_tokens[$index] : null;
	}
}