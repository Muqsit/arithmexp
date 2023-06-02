<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

final class FunctionCallToken extends SimpleToken{

	public function __construct(
		Position $position,
		readonly public string $function,
		readonly public int $argument_count
	){
		parent::__construct(TokenType::FUNCTION_CALL(), $position);
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->function, $this->argument_count);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		$state->parser->function_registry->get($this->function)->writeExpressionTokens($state->parser, $state->expression, $this, $this->function, $this->argument_count, $state);
	}

	public function __debugInfo() : array{
		$info = parent::__debugInfo();
		$info["function"] = $this->function;
		$info["argument_count"] = $this->argument_count;
		return $info;
	}

	public function jsonSerialize() : string{
		return $this->function;
	}
}