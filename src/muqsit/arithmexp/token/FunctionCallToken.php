<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

final class FunctionCallToken extends SimpleToken{

	public function __construct(
		Position $position,
		private string $function,
		private int $argument_count
	){
		parent::__construct(TokenType::FUNCTION_CALL(), $position);
	}

	public function getFunction() : string{
		return $this->function;
	}

	public function getArgumentCount() : int{
		return $this->argument_count;
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->function, $this->argument_count);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		$function = $state->parser->getFunctionRegistry()->get($this->function);
		$state->tokens[] = new FunctionCallExpressionToken($this->position, $this->function, $this->argument_count, $function->closure, $function->flags, $this);
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