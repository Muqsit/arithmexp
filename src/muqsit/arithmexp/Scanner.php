<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use muqsit\arithmexp\operator\OperatorManager;
use muqsit\arithmexp\token\builder\BinaryOperatorTokenBuilder;
use muqsit\arithmexp\token\builder\FunctionCallTokenBuilder;
use muqsit\arithmexp\token\builder\IdentifierTokenBuilder;
use muqsit\arithmexp\token\builder\NumericLiteralTokenBuilder;
use muqsit\arithmexp\token\builder\ParenthesisTokenBuilder;
use muqsit\arithmexp\token\builder\TokenBuilder;
use muqsit\arithmexp\token\builder\TokenBuilderState;
use muqsit\arithmexp\token\builder\UnaryOperatorTokenBuilder;
use muqsit\arithmexp\token\Token;
use RuntimeException;

final class Scanner{

	public static function createDefault(OperatorManager $operator_manager) : self{
		return new self([
			new ParenthesisTokenBuilder(),
			new NumericLiteralTokenBuilder(),
			new FunctionCallTokenBuilder(),
			new IdentifierTokenBuilder(),
			UnaryOperatorTokenBuilder::createDefault($operator_manager->unary_registry),
			BinaryOperatorTokenBuilder::createDefault($operator_manager->binary_registry)
		]);
	}

	/**
	 * @param list<TokenBuilder> $token_builders
	 */
	public function __construct(
		private array $token_builders
	){}

	/**
	 * Scans a given expression and interprets it as a series of tokens.
	 *
	 * @param string $expression
	 * @return list<Token>
	 * @throws ParseException
	 */
	public function scan(string $expression) : array{
		reset($this->token_builders);
		$state = TokenBuilderState::fromExpression($expression);
		while($state->offset < $state->length){
			if($state->expression[$state->offset] === " "){ // ignore space
				$state->offset++;
				continue;
			}

			$scanner = current($this->token_builders);
			if($scanner === false){
				$scanner = reset($this->token_builders);
				if($scanner === false){
					throw new RuntimeException("No token scanner could be found");
				}
			}

			$last_token_end = null;
			foreach($scanner->build($state) as $token){
				$last_token_end = $token->getPos()->end;
				$state->captured_tokens[] = $token;
			}

			next($this->token_builders);
			if($last_token_end === null){
				if(++$state->unknown_token_seq === count($this->token_builders)){
					throw ParseException::unexpectedTokenWhenParsing($state);
				}
				continue;
			}

			$state->offset = $last_token_end;
			$state->unknown_token_seq = 0;
		}

		foreach($this->token_builders as $scanner){
			$scanner->transform($state);
		}

		return $state->captured_tokens;
	}
}