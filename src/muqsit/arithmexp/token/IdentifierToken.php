<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use InvalidArgumentException;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

final class IdentifierToken extends SimpleToken{

	public function __construct(
		Position $position,
		readonly public string $label
	){
		parent::__construct(TokenType::IDENTIFIER(), $position);
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->label);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		try{
			$info = $state->parser->constant_registry->get($this->label);
		}catch(InvalidArgumentException){
			$state->current_group[$state->current_index] = new VariableExpressionToken($this->position, $this->label);
			return;
		}
		$info->writeExpressionTokens($state->parser, $state->expression, $this, $state);
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