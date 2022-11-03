<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use Generator;
use muqsit\arithmexp\operator\binary\BinaryOperator;
use muqsit\arithmexp\operator\OperatorList;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\token\UnaryOperatorToken;

interface OperatorAssignment{

	public const TYPE_LEFT = 0;
	public const TYPE_RIGHT = 1;
	public const TYPE_NA = 2;

	/**
	 * @return self::TYPE_*
	 */
	public function getType() : int;

	/**
	 * @param OperatorList $list
	 * @param Token[]|Token[][] $tokens
	 * @return Generator<OperatorAssignmentTraverserState>
	 */
	public function traverse(OperatorList $list, array &$tokens) : Generator;
}