<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\operator\unary\UnaryOperatorRegistry;
use muqsit\arithmexp\token\IdentifierToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\RightParenthesisToken;
use muqsit\arithmexp\token\UnaryOperatorToken;
use function array_keys;
use function strlen;
use function usort;

final class UnaryOperatorTokenBuilder implements TokenBuilder{

	public static function createDefault(UnaryOperatorRegistry $unary_operator_registry) : self{
		$operators = array_keys($unary_operator_registry->getRegistered());
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
			!($token instanceof NumericLiteralToken) &&
			!($token instanceof RightParenthesisToken) &&
			!($token instanceof IdentifierToken)
		){
			$offset = $state->offset;
			$expression = $state->expression;
			foreach($this->operators as $operator){
				if(
					(!($token instanceof UnaryOperatorToken) || $token->getOperator() !== $operator) &&
					substr($expression, $offset, strlen($operator)) === $operator
				){
					yield new UnaryOperatorToken($offset, $offset + strlen($operator), $operator);
					break;
				}
			}
		}
	}

	public function transform(TokenBuilderState $state) : void{
	}
}