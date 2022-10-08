<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;

final class NumericLiteralToken extends SimpleToken{

	public function __construct(
		Position $position,
		private int|float $value
	){
		parent::__construct(TokenType::NUMERIC_LITERAL(), $position);
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