<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use JsonSerializable;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\Position;

interface Token extends JsonSerializable{

	public function getType() : TokenType;

	public function getPos() : Position;

	public function repositioned(Position $position) : self;

	/**
	 * @param Parser $parser
	 * @param string $expression
	 * @return ExpressionToken
	 * @throws ParseException
	 */
	public function toExpressionToken(Parser $parser, string $expression) : ExpressionToken;
}