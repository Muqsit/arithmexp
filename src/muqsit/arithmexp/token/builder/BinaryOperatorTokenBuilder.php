<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\RightParenthesisToken;
use muqsit\arithmexp\token\VariableToken;

final class BinaryOperatorTokenBuilder implements TokenBuilder{

	public static function createDefault() : self{
		return new self([
			BinaryOperatorToken::OPERATOR_TYPE_DIVISION,
			BinaryOperatorToken::OPERATOR_TYPE_MULTIPLICATION,
			BinaryOperatorToken::OPERATOR_TYPE_ADDITION,
			BinaryOperatorToken::OPERATOR_TYPE_SUBTRACTION
		]);
	}

	/**
	 * @param array<BinaryOperatorToken::OPERATOR_TYPE_*> $operators
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