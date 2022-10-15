<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\Parser;
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

	public function toExpressionToken(Parser $parser, string $expression) : ExpressionToken{
		$operator = $parser->getUnaryOperatorRegistry()->get($this->operator);
		return new FunctionCallExpressionToken($this->position, "({$operator->getSymbol()})", 1, $operator->getOperator(), $operator->isDeterministic(), false, $this);
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