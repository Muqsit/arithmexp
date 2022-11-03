<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\token\UnaryOperatorToken;

final class OperatorAssignmentTraverserState{

	public int $index;
	public BinaryOperatorToken|UnaryOperatorToken $value;
	public bool $changed = false;

	/**
	 * @param Token[] $tokens
	 */
	public function __construct(
		private array &$tokens
	){}

	/**
	 * @param int $offset
	 * @param int $length
	 * @param Token[]|Token[][] $replacement
	 */
	public function splice(int $offset, int $length, array $replacement) : void{
		array_splice($this->tokens, $offset, $length, $replacement);
		$this->changed = true;
	}
}