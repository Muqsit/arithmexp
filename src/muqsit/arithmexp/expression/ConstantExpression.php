<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

final class ConstantExpression implements Expression{
	use GenericExpressionTrait{
		__construct as __parentConstruct;
		__debugInfo as __parentDebugInfo;
	}

	public function __construct(
		string $expression,
		readonly private int|float $value
	){
		$this->__parentConstruct($expression, []);
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