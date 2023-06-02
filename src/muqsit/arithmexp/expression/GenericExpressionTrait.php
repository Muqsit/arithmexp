<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use Generator;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use function array_map;
use function implode;

trait GenericExpressionTrait{

	/**
	 * @param string $expression
	 * @param list<ExpressionToken> $postfix_expression_tokens
	 */
	public function __construct(
		readonly private string $expression,
		readonly private array $postfix_expression_tokens
	){}

	final public function getExpression() : string{
		return $this->expression;
	}

	/**
	 * @return list<ExpressionToken>
	 */
	final public function getPostfixExpressionTokens() : array{
		return $this->postfix_expression_tokens;
	}

	/**
	 * @return Generator<string>
	 */
	final public function findVariables() : Generator{
		foreach($this->postfix_expression_tokens as $token){
			if($token instanceof VariableExpressionToken){
				yield $token->label;
			}
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	final public function __debugInfo() : array{
		return [
			"expression" => $this->expression,
			"postfix" => implode(" ", array_map("strval", $this->postfix_expression_tokens))
		];
	}
}