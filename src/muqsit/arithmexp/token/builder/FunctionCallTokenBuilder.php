<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\FunctionCallArgumentSeparatorToken;
use muqsit\arithmexp\token\FunctionCallToken;
use muqsit\arithmexp\token\IdentifierToken;
use muqsit\arithmexp\token\ParenthesisToken;
use function count;

final class FunctionCallTokenBuilder implements TokenBuilder{

	public function __construct(){
	}

	public function build(TokenBuilderState $state) : Generator{
		$char = $state->expression[$state->offset];
		if($char === ","){
			yield new FunctionCallArgumentSeparatorToken(new Position($state->offset, $state->offset + 1));
		}
	}

	public function transform(TokenBuilderState $state) : void{
		$count = count($state->captured_tokens);
		for($i = $count - 1; $i >= 0; --$i){
			$token = $state->captured_tokens[$i];

			if(!($token instanceof IdentifierToken) || !isset($state->captured_tokens[$i + 1])){
				continue;
			}

			$next_token = $state->captured_tokens[$i + 1];
			if(
				!($next_token instanceof ParenthesisToken) ||
				$next_token->getParenthesisMark() !== ParenthesisToken::MARK_OPENING ||
				$next_token->getParenthesisType() !== ParenthesisToken::TYPE_ROUND
			){
				continue;
			}

			$open_parentheses = 0;
			$argument_count = 0;
			$end_pos = $token->getPos()->end;
			for($j = $i + 2; $j < $count; ++$j){
				$inner_token = $state->captured_tokens[$j];
				if($inner_token instanceof ParenthesisToken){
					if($inner_token->getParenthesisType() !== ParenthesisToken::TYPE_ROUND){
						continue;
					}

					$parenthesis_type = $inner_token->getParenthesisMark();
					if($parenthesis_type === ParenthesisToken::MARK_OPENING){
						++$open_parentheses;
						continue;
					}
					if($parenthesis_type === ParenthesisToken::MARK_CLOSING){
						if(--$open_parentheses < 0){
							$end_pos = $inner_token->getPos()->end;
							break;
						}
						continue;
					}
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

			$state->captured_tokens[$i] = new FunctionCallToken(new Position($token->getPos()->start, $end_pos), $token->getLabel(), $argument_count);
		}
	}
}