<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\OpcodeExpressionToken;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

final class OpcodeToken extends SimpleToken{

	public const OP_BINARY_ADD = 0;
	public const OP_BINARY_AND_SYMBOL = 1;
	public const OP_BINARY_AND_TEXTUAL = 2;
	public const OP_BINARY_DIV = 3;
	public const OP_BINARY_EQUAL = 4;
	public const OP_BINARY_EQUAL_NOT = 5;
	public const OP_BINARY_EXP = 6;
	public const OP_BINARY_GREATER_THAN = 7;
	public const OP_BINARY_GREATER_THAN_EQUAL_TO = 8;
	public const OP_BINARY_IDENTICAL = 9;
	public const OP_BINARY_IDENTICAL_NOT = 10;
	public const OP_BINARY_LESSER_THAN = 11;
	public const OP_BINARY_LESSER_THAN_EQUAL_TO = 12;
	public const OP_BINARY_MOD = 13;
	public const OP_BINARY_MUL = 14;
	public const OP_BINARY_OR_SYMBOL = 15;
	public const OP_BINARY_OR_TEXTUAL = 16;
	public const OP_BINARY_SPACESHIP = 17;
	public const OP_BINARY_SUB = 18;
	public const OP_BINARY_XOR = 19;
	public const OP_UNARY_NOT = 20;
	public const OP_UNARY_NVE = 21;
	public const OP_UNARY_PVE = 22;

	/**
	 * @param self::OP_* $code
	 * @return string
	 */
	public static function opcodeToString(int $code) : string{
		return match($code){
			self::OP_BINARY_ADD, self::OP_UNARY_PVE => "+",
			self::OP_BINARY_AND_SYMBOL => "&&",
			self::OP_BINARY_AND_TEXTUAL => "and",
			self::OP_BINARY_DIV => "/",
			self::OP_BINARY_EQUAL => "==",
			self::OP_BINARY_EQUAL_NOT => "!=",
			self::OP_BINARY_EXP => "**",
			self::OP_BINARY_GREATER_THAN => ">",
			self::OP_BINARY_GREATER_THAN_EQUAL_TO => ">=",
			self::OP_BINARY_IDENTICAL => "===",
			self::OP_BINARY_IDENTICAL_NOT => "!==",
			self::OP_BINARY_LESSER_THAN => "<",
			self::OP_BINARY_LESSER_THAN_EQUAL_TO => "<=",
			self::OP_BINARY_MOD => "%",
			self::OP_BINARY_MUL => "*",
			self::OP_BINARY_OR_SYMBOL => "||",
			self::OP_BINARY_OR_TEXTUAL => "or",
			self::OP_BINARY_SUB, self::OP_UNARY_NVE => "-",
			self::OP_BINARY_XOR => "xor",
			self::OP_UNARY_NOT => "!"
		};
	}

	/**
	 * @param Position $position
	 * @param self::OP_* $code
	 * @param Token|null $parent
	 */
	public function __construct(
		Position $position,
		readonly public int $code,
		readonly public ?Token $parent = null
	){
		parent::__construct(TokenType::OPCODE(), $position);
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->code, $this->parent);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		$state->current_group[$state->current_index] = new OpcodeExpressionToken($this->position, $this->code, $this->parent);
	}

	public function __debugInfo() : array{
		$info = parent::__debugInfo();
		$info["code"] = $this->code;
		return $info;
	}

	public function jsonSerialize() : string{
		return self::opcodeToString($this->code);
	}
}