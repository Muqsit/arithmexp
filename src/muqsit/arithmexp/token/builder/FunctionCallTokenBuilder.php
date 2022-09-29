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
		$right_parentheses = 0;
		$count = count($state->captured_tokens);
		for($i = $count - 1; $i >= 0; --$i){
			$token = $state->captured_tokens[$i];
			if($token instanceof IdentifierToken){
				if(isset($state->captured_tokens[$i + 1]) && $state->captured_tokens[$i + 1] instanceof LeftParenthesisToken && $right_parentheses > 0){
					$argument_count = 0;
					for($j = $i + 2; $j < $count; ++$j){
						$inner_token = $state->captured_tokens[$j];
						if($inner_token instanceof LeftParenthesisToken){
							$open = 1;
							while($j < $count && $open > 0){
								$inner_token = $state->captured_tokens[++$j];
								if($inner_token instanceof LeftParenthesisToken){
									++$open;
								}elseif($inner_token instanceof RightParenthesisToken){
									--$open;
								}
							}
							++$j;
							continue;
						}
						if($inner_token instanceof RightParenthesisToken){
							break;
						}
						if($inner_token instanceof FunctionCallArgumentSeparatorToken){
							if($argument_count === 0){
								$argument_count = 2;
							}else{
								++$argument_count;
							}
						}elseif($argument_count === 0){
							++$argument_count;
						}
					}
					$state->captured_tokens[$i] = new FunctionCallToken($token->getStartPos(), $token->getEndPos(), $token->getLabel(), $argument_count);
					--$right_parentheses;
				}
			}elseif($token instanceof RightParenthesisToken){
				++$right_parentheses;
			}
		}
	}
}