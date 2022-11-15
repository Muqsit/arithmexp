<?php

declare(strict_types=1);

namespace muqsit\arithmexp\constant;

use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;
use muqsit\arithmexp\token\IdentifierToken;

final class SimpleConstantInfo implements ConstantInfo{

	public function __construct(
		public int|float $value
	){}

	public function writeExpressionTokens(Parser $parser, string $expression, IdentifierToken $token, ExpressionTokenBuilderState $state) : void{
		$state->current_group[$state->current_index] = new NumericLiteralExpressionToken($token->getPos(), $this->value);
	}
}