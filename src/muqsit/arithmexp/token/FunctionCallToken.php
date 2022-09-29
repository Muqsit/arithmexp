<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class FunctionCallToken extends SimpleToken{

	public function __construct(
		int $start_pos,
		int $end_pos,
		private string $function,
		private int $argument_count
	){
		parent::__construct(TokenType::FUNCTION_CALL(), $start_pos, $end_pos);
	}

	public function getFunction() : string{
		return $this->function;
	}

	public function getArgumentCount() : int{
		return $this->argument_count;
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