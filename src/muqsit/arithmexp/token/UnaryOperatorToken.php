<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;

final class UnaryOperatorToken extends SimpleToken{

	public function __construct(
		Position $position,
		private string $operator
	){
		parent::__construct(TokenType::UNARY_OPERATOR(), $position);
	}

	public function getOperator() : string{
		return $this->operator;
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->operator);
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