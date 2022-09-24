<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

abstract class SimpleToken implements Token{

	public function __construct(
		protected TokenType $type,
		protected int $start_pos,
		protected int $end_pos
	){}

	final public function getType() : TokenType{
		return $this->type;
	}

	final public function getStartPos() : int{
		return $this->start_pos;
	}

	final public function getEndPos() : int{
		return $this->end_pos;
	}

	public function __debugInfo() : array{
		return [
			"type" => $this->type->getName(),
			"pos" => [
				"start" => $this->start_pos,
				"end" => $this->end_pos
			]
		];
	}
}