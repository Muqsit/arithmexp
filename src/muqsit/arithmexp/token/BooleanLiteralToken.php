<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\BooleanLiteralExpressionToken;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

final class BooleanLiteralToken extends SimpleToken{

	public function __construct(
		Position $position,
		readonly public bool $value
	){
		parent::__construct(TokenType::BOOLEAN_LITERAL(), $position);
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->value);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		$state->current_group[$state->current_index] = new BooleanLiteralExpressionToken($this->position, $this->value);
	}

	public function __debugInfo() : array{
		$info = parent::__debugInfo();
		$info["value"] = $this->value;
		return $info;
	}

	public function jsonSerialize() : string{
		return $this->value ? "true" : "false";
	}
}