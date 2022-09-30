<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\token\FunctionCallArgumentSeparatorToken;
use muqsit\arithmexp\token\FunctionCallToken;
use muqsit\arithmexp\token\IdentifierToken;
use muqsit\arithmexp\token\LeftParenthesisToken;
use muqsit\arithmexp\token\RightParenthesisToken;
use function count;

final class FunctionCallTokenBuilder implements TokenBuilder{

	public function __construct(){
	}

	public function build(TokenBuilderState $state) : Generator{
		$char = $state->expression[$state->offset];
		if($char === ","){
			yield new FunctionCallArgumentSeparatorToken($state->offset, $state->offset + 1);
		}
	}

	public function transform(TokenBuilderState $state) : void{
		$count = count($state->captured_tokens);
		for($i = $count - 1; $i >= 0; --$i){
			$token = $state->captured_tokens[$i];

			if(
				!($token instanceof IdentifierToken) ||
				!isset($state->captured_tokens[$i + 1]) ||
				!($state->captured_tokens[$i + 1] instanceof LeftParenthesisToken)
			){
				continue;
			}

			$open_parentheses = 0;
			$argument_count = 0;
			for($j = $i + 2; $j < $count; ++$j){
				$inner_token = $state->captured_tokens[$j];
				if($inner_token instanceof LeftParenthesisToken){
					++$open_parentheses;
					continue;
				}

				if($inner_token instanceof RightParenthesisToken){
					if(--$open_parentheses < 0){
						break;
					}
					continue;
				}

				if($open_parentheses > 0){
					continue;
				}

				if($argument_count === 0){
					$argument_count = $inner_token instanceof FunctionCallArgumentSeparatorToken ? 2 : 1;
					continue;
				}

				if($inner_token instanceof FunctionCallArgumentSeparatorToken){
					++$argument_count;
				}
			}

			$state->captured_tokens[$i] = new FunctionCallToken($token->getStartPos(), $token->getEndPos(), $token->getLabel(), $argument_count);
		}
	}
}