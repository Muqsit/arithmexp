<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\Position;

final class ParenthesisToken extends SimpleToken{

	public const MARK_OPENING = 0;
	public const MARK_CLOSING = 1;

	public const TYPE_ROUND = 0;

	/**
	 * @param self::MARK_* $mark
	 * @param self::TYPE_* $type
	 * @return string
	 */
	public static function symbolFrom(int $mark, int $type) : string{
		return match($type){
			self::TYPE_ROUND => match($mark){
				self::MARK_OPENING => "(",
				self::MARK_CLOSING => ")"
			}
		};
	}

	/**
	 * @param Position $position
	 * @param self::MARK_* $parenthesis_mark
	 * @param self::TYPE_* $parenthesis_type
	 */
	public function __construct(
		Position $position,
		private int $parenthesis_mark,
		private int $parenthesis_type
	){
		parent::__construct(TokenType::PARENTHESIS(), $position);
	}

	/**
	 * @return self::MARK_*
	 */
	public function getParenthesisMark() : int{
		return $this->parenthesis_mark;
	}

	/**
	 * @return self::TYPE_*
	 */
	public function getParenthesisType() : int{
		return $this->parenthesis_type;
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->parenthesis_mark, $this->parenthesis_type);
	}

	public function toExpressionToken(Parser $parser, string $expression) : ExpressionToken{
		throw ParseException::unexpectedToken($expression, $this);
	}

	public function __debugInfo() : array{
		$info = parent::__debugInfo();
		$info["p_mark"] = $this->parenthesis_mark;
		$info["p_type"] = $this->parenthesis_type;
		$info["symbol"] = self::symbolFrom($this->parenthesis_mark, $this->parenthesis_type);
		return $info;
	}

	public function jsonSerialize() : string{
		return self::symbolFrom($this->parenthesis_mark, $this->parenthesis_type);
	}
}