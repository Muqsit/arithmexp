<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\RightParenthesisToken;
use muqsit\arithmexp\token\UnaryOperatorToken;
use muqsit\arithmexp\token\VariableToken;

final class UnaryOperatorTokenBuilder implements TokenBuilder{

	public static function createDefault() : self{
		return new self([
			UnaryOperatorToken::OPERATOR_NEGATIVE,
			UnaryOperatorToken::OPERATOR_POSITIVE
		]);
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
			!($token instanceof VariableToken)
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
}