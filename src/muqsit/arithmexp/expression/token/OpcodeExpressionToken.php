<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\OpcodeToken;
use muqsit\arithmexp\token\Token;

final class OpcodeExpressionToken implements ExpressionToken{

	/**
	 * @param Position $position
	 * @param OpcodeToken::OP_* $code
	 * @param Token|null $parent
	 */
	public function __construct(
		public Position $position,
		public int $code,
		public ?Token $parent = null
	){}

	public function getPos() : Position{
		return $this->position;
	}

	public function isDeterministic() : bool{
		return true;
	}

	public function equals(ExpressionToken $other) : bool{
		return $other instanceof self && $other->code === $this->code;
	}

	public function __toString() : string{
		return OpcodeToken::opcodeToString($this->code);
	}
}