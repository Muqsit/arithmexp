<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use RuntimeException;
use function array_slice;

final class RawExpression implements Expression{
	use GenericExpressionTrait;

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