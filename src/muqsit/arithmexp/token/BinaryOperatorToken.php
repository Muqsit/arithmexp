<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;
use muqsit\arithmexp\Util;

final class BinaryOperatorToken extends SimpleToken{

	public function __construct(
		Position $position,
		private string $operator
	){
		parent::__construct(TokenType::BINARY_OPERATOR(), $position);
	}

	public function getOperator() : string{
		return $this->operator;
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->operator);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		$operator = $state->parser->getOperatorManager()->getBinaryRegistry()->get($this->operator);

		$argument_count = 2;
		$group = array_slice($state->current_group, $state->current_index - $argument_count, $argument_count);
		Util::flattenArray($group);
		$pos = Util::positionContainingTokens($group);
		$state->current_group[$state->current_index] = new FunctionCallExpressionToken($pos, $operator->getSymbol(), $argument_count, $operator->getOperator(), $operator->getFlags(), $this);
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