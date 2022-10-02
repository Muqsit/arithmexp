<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use Exception;
use muqsit\arithmexp\function\FunctionInfo;
use muqsit\arithmexp\token\builder\TokenBuilderState;
use muqsit\arithmexp\token\FunctionCallToken;
use muqsit\arithmexp\token\Token;
use Throwable;
use function count;
use function sprintf;
use function strlen;
use function substr;

final class ParseException extends Exception{

	public const ERR_EXPR_EMPTY = 100001;
	public const ERR_NO_CLOSING_PAREN = 100002;
	public const ERR_NO_OPENING_PAREN = 100003;
	public const ERR_NO_OPERAND_BINARY = 100004;
	public const ERR_NO_OPERAND_UNARY = 100005;
	public const ERR_UNEXPECTED_TOKEN = 100006;
	public const ERR_UNRESOLVABLE_FCALL = 100007;

	public static function emptyExpression(string $expression) : self{
		return new self($expression, 0, strlen($expression), sprintf("Expression \"%s\" is empty", $expression), self::ERR_EXPR_EMPTY);
	}

	public static function noClosingParenthesis(string $expression, Token $token) : self{
		return new self($expression, $token->getStartPos(), $token->getEndPos(), sprintf(
			"No closing parenthesis specified for opening parenthesis at \"%s\" (%d:%d) in \"%s\"",
			substr($expression, $token->getStartPos(), $token->getEndPos() - $token->getStartPos()),
			$token->getStartPos(),
			$token->getEndPos(),
			$expression
		), self::ERR_NO_CLOSING_PAREN);
	}

	public static function noOpeningParenthesis(string $expression, Token $token) : self{
		return new self($expression, $token->getStartPos(), $token->getEndPos(), sprintf(
			"No opening parenthesis specified for closing parenthesis at \"%s\" (%d:%d) in \"%s\"",
			substr($expression, $token->getStartPos(), $token->getEndPos() - $token->getStartPos()),
			$token->getStartPos(),
			$token->getEndPos(),
			$expression
		), self::ERR_NO_OPENING_PAREN);
	}

	/**
	 * @param string $expression
	 * @param Token $token
	 * @param "left"|"right" $side
	 * @return self
	 */
	public static function noOperandForBinaryOperation(string $expression, Token $token, string $side) : self{
		return new self($expression, $token->getStartPos(), $token->getEndPos(), sprintf(
			"No %s operand specified for binary operator at \"%s\" (%d:%d) in \"%s\"",
			$side,
			substr($expression, $token->getStartPos(), $token->getEndPos() - $token->getStartPos()),
			$token->getStartPos(),
			$token->getEndPos(),
			$expression
		), self::ERR_NO_OPERAND_BINARY);
	}

	public static function noOperandForUnaryOperation(string $expression, Token $token) : self{
		return new self($expression, $token->getStartPos(), $token->getEndPos(), sprintf(
			"No right operand specified for unary operator at \"%s\" (%d:%d) in \"%s\"",
			substr($expression, $token->getStartPos(), $token->getEndPos() - $token->getStartPos()),
			$token->getStartPos(),
			$token->getEndPos(),
			$expression
		), self::ERR_NO_OPERAND_UNARY);
	}

	public static function unexpectedToken(string $expression, Token $token) : self{
		return new self($expression, $token->getStartPos(), $token->getEndPos(), sprintf(
			"Unexpected %s token encountered at \"%s\" (%d:%d) in \"%s\"",
			$token->getType()->getName(),
			substr($expression, $token->getStartPos(), $token->getEndPos() - $token->getStartPos()),
			$token->getStartPos(),
			$token->getEndPos(),
			$expression
		), self::ERR_UNEXPECTED_TOKEN);
	}

	public static function unexpectedTokenWhenParsing(TokenBuilderState $state) : self{
		return new self($state->expression, $state->offset, $state->length, sprintf(
			"Unexpected token encountered at (%d:%d) \"%s\" when parsing \"%s\"",
			$state->offset,
			$state->length,
			substr($state->expression, $state->offset, $state->length - $state->offset),
			$state->expression
		), self::ERR_UNEXPECTED_TOKEN);
	}

	public static function unresolvableFcallGeneric(string $expression, Token $token, string $reason, ?Throwable $previous = null) : self{
		return new self($expression, $token->getStartPos(), $token->getEndPos(), sprintf(
			"Cannot resolve function call at \"%s\" (%d:%d) in \"%s\": %s",
			substr($expression, $token->getStartPos(), $token->getEndPos() - $token->getStartPos()),
			$token->getStartPos(),
			$token->getEndPos(),
			$expression,
			$reason
		), self::ERR_UNRESOLVABLE_FCALL, $previous);
	}

	public static function unresolvableFcallNoDefaultParamValue(string $expression, FunctionCallToken $token, int $parameter) : self{
		return self::unresolvableFcallGeneric($expression, $token, sprintf("Function \"%s\" does not have a default value for parameter #%d", $token->getFunction(), $parameter));
	}

	public static function unresolvableFcallTooManyParams(string $expression, FunctionCallToken $token, FunctionInfo $function, int $params_c) : self{
		return self::unresolvableFcallGeneric($expression, $token, sprintf(
			"Too many parameters supplied to function call: Expected %d parameter%s, got %d parameter%s",
			count($function->fallback_param_values),
			count($function->fallback_param_values) === 1 ? "" : "s",
			$params_c,
			$params_c === 1 ? "" : "s"
		));
	}

	public function __construct(
		private string $expression,
		private int $start_pos,
		private int $end_pos,
		string $message = "",
		int $code = 0,
		?Throwable $previous = null
	){
		parent::__construct($message, $code, $previous);
	}

	public function getExpression() : string{
		return $this->expression;
	}

	public function getStartPos() : int{
		return $this->start_pos;
	}

	public function getEndPos() : int{
		return $this->end_pos;
	}

	public function getExpressionSubstring() : string{
		return substr($this->expression, $this->start_pos, $this->end_pos - $this->start_pos);
	}
}