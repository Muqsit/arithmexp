<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use Generator;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use RuntimeException;
use function array_map;
use function array_slice;
use function implode;

final class Expression{

	/**
	 * @param string $expression
	 * @param ExpressionToken[] $postfix_expression_tokens
	 */
	public function __construct(
		private string $expression,
		private array $postfix_expression_tokens
	){}

	public function getExpression() : string{
		return $this->expression;
	}

	/**
	 * @return ExpressionToken[]
	 */
	public function getPostfixExpressionTokens() : array{
		return $this->postfix_expression_tokens;
	}

	/**
	 * @return Generator<string>
	 */
	public function getVariables() : Generator{
		foreach($this->postfix_expression_tokens as $token){
			if($token instanceof VariableExpressionToken){
				yield $token->label;
			}
		}
	}

	/**
	 * @param array<string, int|float> $variable_values
	 * @return int|float
	 */
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

	/**
	 * @return array<string, mixed>
	 */
	public function __debugInfo() : array{
		return [
			"expression" => $this->expression,
			"postfix" => implode(" ", array_map("strval", $this->postfix_expression_tokens))
		];
	}
}