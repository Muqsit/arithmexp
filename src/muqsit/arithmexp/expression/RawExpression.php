<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use RuntimeException;
use function array_filter;
use function array_map;
use function array_slice;
use function array_splice;
use function count;

final class RawExpression implements Expression{
	use GenericExpressionTrait;

	public function precomputed() : Expression{
		$postfix_expression_tokens = $this->postfix_expression_tokens;
		do{
			$found = false;
			foreach($postfix_expression_tokens as $i => $token){
				if(!($token instanceof FunctionCallExpressionToken) || !$token->isDeterministic()){
					continue;
				}

				$params = array_slice($postfix_expression_tokens, $i - $token->argument_count, $token->argument_count);
				if(count(array_filter($params, static fn(ExpressionToken $token) : bool => $token instanceof FunctionCallExpressionToken || !$token->isDeterministic())) > 0){
					continue;
				}

				array_splice($postfix_expression_tokens, $i - $token->argument_count, $token->argument_count + 1, [new NumericLiteralExpressionToken(($token->function)(...array_map(fn(ExpressionToken $token) : int|float => $token->getValue($this, []), $params)))]);
				$found = true;
				break;
			}
		}while($found);
		return count($postfix_expression_tokens) === 1 && $postfix_expression_tokens[0]->isDeterministic() ?
			new ConstantExpression($this->expression, $this->postfix_expression_tokens, $postfix_expression_tokens[0]->getValue($this, [])) :
			new self($this->expression, $postfix_expression_tokens);
	}

	public function evaluate(array $variable_values = []) : int|float{
		$stack = [];
		$ptr = -1;
		foreach($this->postfix_expression_tokens as $token){
			if($token instanceof FunctionCallExpressionToken){
				$ptr -= $token->argument_count - 1;
				$stack[$ptr] = ($token->function)(...array_slice($stack, $ptr, $token->argument_count));
			}else{
				$stack[++$ptr] = $token->getValue($this, $variable_values);
			}
		}

		return $stack[$ptr] ?? throw new RuntimeException("Could not evaluate \"{$this->expression}\"");
	}
}