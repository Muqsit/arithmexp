<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary;

use muqsit\arithmexp\function\FunctionInfo;
use muqsit\arithmexp\operator\assignment\OperatorAssignment;

final class SimpleBinaryOperator implements BinaryOperator{

	public function __construct(
		private string $symbol,
		private string $name,
		private int $precedence,
		private OperatorAssignment $assignment_type,
		private FunctionInfo $function
	){}

	public function getSymbol() : string{
		return $this->symbol;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getPrecedence() : int{
		return $this->precedence;
	}

	public function getAssignment() : OperatorAssignment{
		return $this->assignment_type;
	}

	public function getFunction() : FunctionInfo{
		return $this->function;
	}
}