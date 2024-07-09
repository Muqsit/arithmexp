<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use Generator;
use muqsit\arithmexp\operator\OperatorList;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\token\Token;

interface OperatorAssignment{

	public const TYPE_LEFT = 0; // x + y + z = (x + y) + z
	public const TYPE_RIGHT = 1; // x ** y ** z = x ** (y ** z)
	public const TYPE_NA = 2; // undefined assignment behaviour
	public const TYPE_NON_ASSOCIATIVE = 3; // x == y == z = illegal. implicit associativity is disallowed.

	/**
	 * @return self::TYPE_*
	 */
	public function getType() : int;

	/**
	 * @param OperatorList $list
	 * @param string $expression
	 * @param list<Token|list<Token>> $tokens
	 * @return Generator<OperatorAssignmentTraverserState>
	 * @throws ParseException
	 */
	public function traverse(OperatorList $list, string $expression, array &$tokens) : Generator;
}