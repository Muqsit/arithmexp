<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use JsonSerializable;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

interface Token extends JsonSerializable{

	public function getType() : TokenType;

	public function getPos() : Position;

	public function repositioned(Position $position) : self;

	/**
	 * @param ExpressionTokenBuilderState $state
	 * @throws ParseException
	 */
	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void;
}