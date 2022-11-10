<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\OpcodeExpressionToken;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;

final class OpcodeToken extends SimpleToken{

	public const OP_BINARY_ADD = 0;
	public const OP_BINARY_DIV = 1;
	public const OP_BINARY_EXP = 2;
	public const OP_BINARY_MOD = 3;
	public const OP_BINARY_MUL = 4;
	public const OP_BINARY_SUB = 5;
	public const OP_UNARY_NVE = 6;
	public const OP_UNARY_PVE = 7;

	/**
	 * @param self::OP_* $code
	 * @return string
	 */
	public static function opcodeToString(int $code) : string{
		return match($code){
			self::OP_BINARY_ADD, self::OP_UNARY_PVE => "+",
			self::OP_BINARY_DIV => "/",
			self::OP_BINARY_EXP => "**",
			self::OP_BINARY_MOD => "%",
			self::OP_BINARY_MUL => "*",
			self::OP_BINARY_SUB, self::OP_UNARY_NVE => "-"
		};
	}

	/**
	 * @param Position $position
	 * @param self::OP_* $code
	 */
	public function __construct(
		Position $position,
		private int $code
	){
		parent::__construct(TokenType::OPCODE(), $position);
	}

	/**
	 * @return self::OP_*
	 */
	public function getCode() : int{
		return $this->code;
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->code);
	}

	public function writeExpressionTokens(ExpressionTokenBuilderState $state) : void{
		$state->current_group[$state->current_index] = new OpcodeExpressionToken($this->position, $this->code);
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