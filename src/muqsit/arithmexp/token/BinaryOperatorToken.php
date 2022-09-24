<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class BinaryOperatorToken extends SimpleToken{

	public const OPERATOR_TYPE_ADDITION = "+";
	public const OPERATOR_TYPE_DIVISION = "/";
	public const OPERATOR_TYPE_MULTIPLICATION = "*";
	public const OPERATOR_TYPE_SUBTRACTION = "-";

	/**
	 * @param int $start_pos
	 * @param int $end_pos
	 * @param self::OPERATOR_TYPE_* $operator
	 */
	public function __construct(
		int $start_pos,
		int $end_pos,
		private string $operator
	){
		parent::__construct(TokenType::BINARY_OPERATOR(), $start_pos, $end_pos);
	}

	/**
	 * @return self::OPERATOR_*
	 */
	public function getOperator() : string{
		return $this->operator;
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