<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;
use muqsit\arithmexp\Util;
use function array_slice;
use function count;

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

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		$operator = $state->parser->getOperatorManager()->getUnaryRegistry()->get($this->operator);

		$argument_count = 1;
		$parameters = array_slice(Util::expressionTokenArrayToTree($state->tokens, 0, count($state->tokens)), -$argument_count);
		Util::flattenArray($parameters);
		$pos = Position::containing([Util::positionContainingExpressionTokens($parameters), $this->position]);
		$state->tokens[] = new FunctionCallExpressionToken($pos, "({$operator->getSymbol()})", $argument_count, $operator->getOperator(), $operator->getFlags(), $this);
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