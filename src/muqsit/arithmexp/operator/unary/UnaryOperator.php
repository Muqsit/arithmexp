<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use muqsit\arithmexp\function\FunctionInfo;

interface UnaryOperator{

	public function getSymbol() : string;

	public function getPrecedence() : int;

	public function getName() : string;

	public function getFunction() : FunctionInfo;
}