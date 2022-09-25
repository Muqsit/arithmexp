<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class BinaryOperatorToken extends SimpleToken{

	public function __construct(
		int $start_pos,
		int $end_pos,
		private string $operator
	){
		parent::__construct(TokenType::BINARY_OPERATOR(), $start_pos, $end_pos);
	}

	public function getOperator() : string{
		return $this->operator;
	}

	public function __debugInfo() : array{
		$info = parent::__debugInfo();
		$info["operator"] = $this->operator;
		return $info;
	}

	public function jsonSerialize() : string{
		return $this->operator;
	}
}