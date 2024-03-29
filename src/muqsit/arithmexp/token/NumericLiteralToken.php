<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

final class NumericLiteralToken extends SimpleToken{

	public function __construct(
		Position $position,
		readonly public int|float $value
	){
		parent::__construct(TokenType::NUMERIC_LITERAL(), $position);
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->value);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		$state->current_group[$state->current_index] = new NumericLiteralExpressionToken($this->position, $this->value);
	}

	public function __debugInfo() : array{
		$info = parent::__debugInfo();
		$info["value"] = $this->value;
		return $info;
	}

	public function jsonSerialize() : string{
		return (string) $this->value;
	}
}