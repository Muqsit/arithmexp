<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\token\FunctionCallArgumentSeparatorToken;
use muqsit\arithmexp\token\FunctionCallToken;
use muqsit\arithmexp\token\LeftParenthesisToken;
use muqsit\arithmexp\token\RightParenthesisToken;
use muqsit\arithmexp\token\VariableToken;
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
		$right_parentheses = 0;
		for($i = count($state->captured_tokens) - 1; $i >= 0; --$i){
			$token = $state->captured_tokens[$i];
			if($token instanceof VariableToken){
				if(isset($state->captured_tokens[$i + 1]) && $state->captured_tokens[$i + 1] instanceof LeftParenthesisToken && $right_parentheses > 0){
					$state->captured_tokens[$i] = new FunctionCallToken($token->getStartPos(), $token->getEndPos(), $token->getLabel());
					--$right_parentheses;
				}
			}elseif($token instanceof RightParenthesisToken){
				++$right_parentheses;
			}
		}
	}
}