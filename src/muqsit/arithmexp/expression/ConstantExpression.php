<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use muqsit\arithmexp\expression\token\ExpressionToken;

final class ConstantExpression implements Expression{
	use GenericExpressionTrait{
		__construct as __parentConstruct;
		__debugInfo as __parentDebugInfo;
	}

	/**
	 * @param string $expression
	 * @param ExpressionToken[] $postfix_expression_tokens
	 * @param int|float $value
	 */
	public function __construct(
		string $expression,
		array $postfix_expression_tokens,
		private int|float $value
	){
		$this->__parentConstruct($expression, $postfix_expression_tokens);
	}

	public function evaluate(array $variable_values = []) : int|float{
		return $this->value;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __debugInfo() : array{
		$result = $this->__parentDebugInfo();
		$result["value"] = $this->value;
		return $result;
	}
}