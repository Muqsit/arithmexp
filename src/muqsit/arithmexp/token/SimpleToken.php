<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;

abstract class SimpleToken implements Token{

	public function __construct(
		protected TokenType $type,
		protected Position $position
	){}

	final public function getType() : TokenType{
		return $this->type;
	}

	final public function getPos() : Position{
		return $this->position;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __debugInfo() : array{
		return [
			"type" => $this->type->getName(),
			"pos" => $this->position
		];
	}
}