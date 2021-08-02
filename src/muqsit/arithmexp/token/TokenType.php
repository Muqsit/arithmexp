<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class TokenType{

	public const INVALID = 0;
	public const WHITESPACE = 1;
	public const BRACKET_OPEN = 2;
	public const BRACKET_CLOSE = 3;
	public const NUMBER = 4;
	public const OPERATOR = 5;
	public const CONSTANT = 6;

	private function __construct(){
	}
}