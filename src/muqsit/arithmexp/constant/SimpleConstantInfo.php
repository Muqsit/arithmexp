<?php

declare(strict_types=1);

namespace muqsit\arithmexp\constant;

use muqsit\arithmexp\expression\token\BooleanLiteralExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;
use muqsit\arithmexp\token\IdentifierToken;
use RuntimeException;
use function gettype;
use function is_float;
use function is_int;

final class SimpleConstantInfo implements ConstantInfo{

	public function __construct(
		readonly public int|float|bool $value
	){}

	public function writeExpressionTokens(Parser $parser, string $expression, IdentifierToken $token, ExpressionTokenBuilderState $state) : void{
		$state->current_group[$state->current_index] = match(true){
			is_int($this->value), is_float($this->value) => new NumericLiteralExpressionToken($token->getPos(), $this->value),
			is_bool($this->value) => new BooleanLiteralExpressionToken($token->getPos(), $this->value),
			default => throw new RuntimeException("Unexpected value type " . gettype($this->value))
		};
	}
}