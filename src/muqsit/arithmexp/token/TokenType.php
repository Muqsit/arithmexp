<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class TokenType{

	public const BINARY_OPERATOR = 0;
	public const NUMERIC_LITERAL = 1;
	public const PARENTHESIS_LEFT = 2;
	public const PARENTHESIS_RIGHT = 3;
	public const UNARY_OPERATOR = 4;
	public const VARIABLE = 5;

	public static function BINARY_OPERATOR() : self{
		static $instance = null;
		return $instance ??= new self(self::BINARY_OPERATOR, "Binary Operator");
	}

	public static function NUMERIC_LITERAL() : self{
		static $instance = null;
		return $instance ??= new self(self::NUMERIC_LITERAL, "Numeric Literal");
	}

	public static function PARENTHESIS_LEFT() : self{
		static $instance = null;
		return $instance ??= new self(self::PARENTHESIS_LEFT, "Left Parenthesis");
	}

	public static function PARENTHESIS_RIGHT() : self{
		static $instance = null;
		return $instance ??= new self(self::PARENTHESIS_RIGHT, "Right Parenthesis");
	}

	public static function UNARY_OPERATOR() : self{
		static $instance = null;
		return $instance ??= new self(self::UNARY_OPERATOR, "Unary Operator");
	}

	public static function VARIABLE() : self{
		static $instance = null;
		return $instance ??= new self(self::VARIABLE, "Variable");
	}

	private function __construct(
		private int $identifier,
		private string $name
	){}

	public function getIdentifier() : int{
		return $this->identifier;
	}

	public function getName() : string{
		return $this->name;
	}

	/**
	 * @param TokenType::* $identifier
	 * @return bool
	 */
	public function is(int $identifier) : bool{
		return $this->identifier === $identifier;
	}

	public function __debugInfo() : array{
		return [
			"identifier" => $this->identifier,
			"name" => $this->name
		];
	}
}