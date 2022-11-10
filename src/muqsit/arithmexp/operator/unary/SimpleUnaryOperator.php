<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use muqsit\arithmexp\function\FunctionInfo;

final class SimpleUnaryOperator implements UnaryOperator{

	public function __construct(
		private string $symbol,
		private string $name,
		private int $precedence,
		private FunctionInfo $function
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