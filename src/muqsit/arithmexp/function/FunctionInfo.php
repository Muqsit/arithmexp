<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;
use muqsit\arithmexp\token\Token;

interface FunctionInfo{

	public function getClosure() : Closure;

	/**
	 * @return list<int|float|null>
	 */
	public function getFallbackParamValues() : array;

	public function isVariadic() : bool;

	/**
	 * @return int-mask-of<FunctionFlags::*>
	 */
	public function getFlags() : int;

	public function writeExpressionTokens(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, ExpressionTokenBuilderState $state) : void;
}