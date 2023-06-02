<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;

abstract class SimpleToken implements Token{

	public function __construct(
		readonly public TokenType $type,
		readonly public Position $position
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
			"type" => $this->type->name,
			"pos" => $this->position
		];
	}
}