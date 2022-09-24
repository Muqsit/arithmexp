<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class UnaryOperatorToken extends SimpleToken{

	public const OPERATOR_TYPE_NEGATIVE = "-";
	public const OPERATOR_TYPE_POSITIVE = "+";

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
		parent::__construct(TokenType::UNARY_OPERATOR(), $start_pos, $end_pos);
	}

	/**
	 * @return self::OPERATOR_*
	 */
	public function getOperator() : string{
		return $this->operator;
	}

	public function getFactor() : float{
		return match($this->operator){
			self::OPERATOR_TYPE_POSITIVE => +1,
			self::OPERATOR_TYPE_NEGATIVE => -1
		};
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