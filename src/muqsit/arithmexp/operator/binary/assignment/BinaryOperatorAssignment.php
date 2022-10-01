<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary\assignment;

use Generator;
use muqsit\arithmexp\operator\binary\BinaryOperator;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\Token;

interface BinaryOperatorAssignment{

	public const TYPE_LEFT = 0;
	public const TYPE_RIGHT = 1;

	/**
	 * @return self::TYPE_*
	 */
	public function getType() : int;

	/**
	 * @param array<string, BinaryOperator> $operators
	 * @param Token[]|Token[][] $tokens
	 * @return Generator<BinaryOperatorToken>
	 */
	public function traverse(array $operators, array &$tokens) : Generator;
}