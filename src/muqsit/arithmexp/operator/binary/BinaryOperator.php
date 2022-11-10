<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary;

use muqsit\arithmexp\function\FunctionInfo;
use muqsit\arithmexp\operator\assignment\OperatorAssignment;

interface BinaryOperator{

	public function getSymbol() : string;

	public function getName() : string;

	public function getPrecedence() : int;

	public function getAssignment() : OperatorAssignment;

	public function getFunction() : FunctionInfo;
}