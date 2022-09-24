<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\token\Token;

interface TokenBuilder{

	/**
	 * Checks if the given expression at the given builder state
	 * can be tokenized before constructing and yielding the token.
	 *
	 * @param TokenBuilderState $state
	 * @return Generator<Token>
	 */
	public function build(TokenBuilderState $state) : Generator;
}