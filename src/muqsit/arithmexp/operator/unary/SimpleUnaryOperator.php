<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use muqsit\arithmexp\function\FunctionInfo;

final class SimpleUnaryOperator implements UnaryOperator{

	public function __construct(
		readonly private string $symbol,
		readonly private string $name,
		readonly private int $precedence,
		readonly private FunctionInfo $function
	){}

	public function getSymbol() : string{
		return $this->symbol;
	}

	public function getPrecedence() : int{
		return $this->precedence;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getFunction() : FunctionInfo{
		return $this->function;
	}
}