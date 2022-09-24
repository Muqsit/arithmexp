<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class NumericLiteralToken extends SimpleToken{

	public function __construct(
		int $start_pos,
		int $end_pos,
		private int|float $value
	){
		parent::__construct(TokenType::NUMERIC_LITERAL(), $start_pos, $end_pos);
	}

	public function getValue() : int|float{
		return $this->value;
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