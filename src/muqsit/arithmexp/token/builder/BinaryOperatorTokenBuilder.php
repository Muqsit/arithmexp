<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\operator\BinaryOperatorRegistry;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\RightParenthesisToken;
use muqsit\arithmexp\token\VariableToken;
use function array_keys;
use function usort;

final class BinaryOperatorTokenBuilder implements TokenBuilder{

	public static function createDefault(BinaryOperatorRegistry $binary_operator_registry) : self{
		$operators = array_keys($binary_operator_registry->getRegistered());
		usort($operators, static fn(string $a, string $b) : int => strlen($b) <=> strlen($a));
		return new self($operators);
	}

	/**
	 * @param string[] $operators
	 */
	public function __construct(
		private array $operators
	){}

	public function build(TokenBuilderState $state) : Generator{
		$token = $state->getLastCapturedToken();
		if(
			$token instanceof NumericLiteralToken ||
			$token instanceof RightParenthesisToken ||
			$token instanceof VariableToken
		){
			$offset = $state->offset;
			$expression = $state->expression;
			foreach($this->operators as $operator){
				if(substr($expression, $offset, strlen($operator)) === $operator){
					yield new BinaryOperatorToken($offset, $offset + strlen($operator), $operator);
					break;
				}
			}
		}
	}
}