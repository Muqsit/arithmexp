<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

final class IdentifierToken extends SimpleToken{

	public function __construct(
		Position $position,
		private string $label
	){
		parent::__construct(TokenType::IDENTIFIER(), $position);
	}

	public function getLabel() : string{
		return $this->label;
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->label);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		$constant_value = $state->parser->getConstantRegistry()->registered[$this->label] ?? null;
		$state->current_group[$state->current_index] = $constant_value !== null ? new NumericLiteralExpressionToken($this->position, $constant_value) : new VariableExpressionToken($this->position, $this->label);
	}

	public function __debugInfo() : array{
		$info = parent::__debugInfo();
		$info["label"] = $this->label;
		return $info;
	}

	public function jsonSerialize() : string{
		return $this->label;
	}
}