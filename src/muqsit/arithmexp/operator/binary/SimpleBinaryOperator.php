<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary;

use muqsit\arithmexp\function\FunctionInfo;
use muqsit\arithmexp\operator\assignment\OperatorAssignment;

final class SimpleBinaryOperator implements BinaryOperator{

	public function __construct(
		readonly private string $symbol,
		readonly private string $name,
		readonly private int $precedence,
		readonly private OperatorAssignment $assignment_type,
		readonly private FunctionInfo $function
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