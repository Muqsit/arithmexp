<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\OpcodeToken;
use muqsit\arithmexp\token\Token;
use RuntimeException;

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

	public function retrieveValue(Expression $expression, array $variables) : int|float{
		throw new RuntimeException("Don't know how to retrieve value of " . self::class);
	}

	public function equals(ExpressionToken $other) : bool{
		return $other instanceof self && $other->code === $this->code;
	}

	public function __toString() : string{
		return OpcodeToken::opcodeToString($this->code);
	}
}