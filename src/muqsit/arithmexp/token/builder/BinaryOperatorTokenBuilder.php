<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\operator\binary\BinaryOperatorRegistry;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\IdentifierToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\ParenthesisToken;
use function array_keys;
use function strlen;
use function usort;

final class BinaryOperatorTokenBuilder implements TokenBuilder{

	public static function createDefault(BinaryOperatorRegistry $binary_operator_registry) : self{
		$instance = new self([]);
		$change_listener = static function(BinaryOperatorRegistry $registry) use($instance) : void{
			$operators = array_keys($registry->getRegistered());
			usort($operators, static fn(string $a, string $b) : int => strlen($b) <=> strlen($a));
			$instance->operators = $operators;
		};
		$change_listener($binary_operator_registry);
		$binary_operator_registry->registerChangeListener($change_listener);
		return $instance;
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
			$token instanceof IdentifierToken ||
			($token instanceof ParenthesisToken && $token->getParenthesisMark() === ParenthesisToken::MARK_CLOSING)
		){
			$offset = $state->offset;
			$expression = $state->expression;
			foreach($this->operators as $operator){
				if(substr($expression, $offset, strlen($operator)) === $operator){
					yield new BinaryOperatorToken(new Position($offset, $offset + strlen($operator)), $operator);
					break;
				}
			}
		}
	}

	public function transform(TokenBuilderState $state) : void{
	}
}