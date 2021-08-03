<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class TokenUtil{

	/**
	 * @param Token[] $tokens
	 */
	public static function sort(array &$tokens) : void{
		usort($tokens, static function(Token $a, Token $b) : int{
			return $a->start_pos <=> $b->start_pos;
		});
	}

	public static function stringifyType(int $type) : string{
		return match($type){
			TokenType::INVALID => "T_INVALID",
			TokenType::WHITESPACE => "T_WHITESPACE",
			TokenType::BRACKET_OPEN => "T_BRACKET_OPEN",
			TokenType::BRACKET_CLOSE => "T_BRACKET_CLOSE",
			TokenType::NUMBER => "T_NUMBER",
			TokenType::OPERATOR => "T_OPERATOR",
			TokenType::SYMBOL => "T_SYMBOL",
			default => "#{$type}"
		};
	}

	public static function stringifyValueAndPosition(Token $token) : string{
		return "'{$token->text}' [" . ($token->start_pos + 1) . ":" . ($token->end_pos + 1) . "]";
	}

	public static function getExpressionPortion(string $expression, Token $token) : string{
		return substr($expression, $token->start_pos, 1 + $token->end_pos - $token->start_pos);
	}

	private function __construct(){
	}
}