<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;
use muqsit\arithmexp\Util;
use function array_slice;

final class BinaryOperatorToken extends SimpleToken{

	public function __construct(
		Position $position,
		readonly private string $operator
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
		$function = $operator->getFunction();

		$argument_count = 2;
		$group = array_slice($state->current_group, $state->current_index - $argument_count, $argument_count);
		Util::flattenArray($group);
		$token = new self(Util::positionContainingTokens($group), $this->operator);
		$function->writeExpressionTokens($state->parser, $state->expression, $token, $operator->getSymbol(), $argument_count, $state);
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