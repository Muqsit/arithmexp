<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\Position;

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

	public function toExpressionToken(Parser $parser, string $expression) : ExpressionToken{
		$constant_value = $parser->getConstantRegistry()->registered[$this->label] ?? null;
		return $constant_value !== null ? new NumericLiteralExpressionToken($this->position, $constant_value) : new VariableExpressionToken($this->position, $this->label);
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