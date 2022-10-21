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
use function str_repeat;
use function strlen;
use function substr;
use const PHP_EOL;

final class ParseException extends Exception{

	public const ERR_EXPR_EMPTY = 100001;
	public const ERR_NO_CLOSING_PAREN = 100002;
	public const ERR_NO_OPENING_PAREN = 100003;
	public const ERR_NO_OPERAND_BINARY_LEFT = 100004;
	public const ERR_NO_OPERAND_BINARY_RIGHT = 100005;
	public const ERR_NO_OPERAND_UNARY = 100006;
	public const ERR_UNEXPECTED_PAREN = 100007;
	public const ERR_UNEXPECTED_TOKEN = 100008;
	public const ERR_UNRESOLVABLE_EXPRESSION = 100009;
	public const ERR_UNRESOLVABLE_FCALL = 100010;

	public static function generateWithHighlightedSubstring(self $exception) : self{
		return new self(
			$exception->expression,
			$exception->position,
			$exception->getMessage() . ($exception->position->length() === strlen($exception->expression) ? "" :
				PHP_EOL .
				" | " . $exception->expression . PHP_EOL .
				" | " . str_repeat(" ", $exception->position->getStart()) . str_repeat("^", $exception->position->length())
			),
			$exception->code,
			$exception->getPrevious()
		);
	}

	public static function emptyExpression(string $expression) : self{
		return self::generateWithHighlightedSubstring(new self($expression, new Position(0, strlen($expression)), sprintf("Expression \"%s\" is empty", $expression), self::ERR_EXPR_EMPTY));
	}

	public static function noBinaryOperandLeft(string $expression, Position $position) : self{
		return self::generateWithHighlightedSubstring(new self($expression, $position, sprintf(
			"No left operand specified for binary operator at \"%s\" (%d:%d) in \"%s\"",
			$position->in($expression),
			$position->getStart(),
			$position->getEnd(),
			$expression
		), self::ERR_NO_OPERAND_BINARY_LEFT));
	}

	public static function noBinaryOperandRight(string $expression, Position $position) : self{
		return self::generateWithHighlightedSubstring(new self($expression, $position, sprintf(
			"No right operand specified for binary operator at \"%s\" (%d:%d) in \"%s\"",
			$position->in($expression),
			$position->getStart(),
			$position->getEnd(),
			$expression
		), self::ERR_NO_OPERAND_BINARY_RIGHT));
	}

	public static function noClosingParenthesis(string $expression, Position $position) : self{
		return self::generateWithHighlightedSubstring(new self($expression, $position, sprintf(
			"No closing parenthesis specified for opening parenthesis at \"%s\" (%d:%d) in \"%s\"",
			$position->in($expression),
			$position->getStart(),
			$position->getEnd(),
			$expression
		), self::ERR_NO_CLOSING_PAREN));
	}

	public static function noOpeningParenthesis(string $expression, Position $position) : self{
		return self::generateWithHighlightedSubstring(new self($expression, $position, sprintf(
			"No opening parenthesis specified for closing parenthesis at \"%s\" (%d:%d) in \"%s\"",
			$position->in($expression),
			$position->getStart(),
			$position->getEnd(),
			$expression
		), self::ERR_NO_OPENING_PAREN));
	}

	public static function unexpectedParenthesisType(string $expression, Position $position) : self{
		return self::generateWithHighlightedSubstring(new self($expression, $position, sprintf(
			"Unexpected parenthesis type specified at \"%s\" (%d:%d) in \"%s\"",
			$position->in($expression),
			$position->getStart(),
			$position->getEnd(),
			$expression
		), self::ERR_UNEXPECTED_PAREN));
	}

	public static function noUnaryOperand(string $expression, Position $position) : self{
		return self::generateWithHighlightedSubstring(new self($expression, $position, sprintf(
			"No operand specified for unary operator at \"%s\" (%d:%d) in \"%s\"",
			$position->in($expression),
			$position->getStart(),
			$position->getEnd(),
			$expression
		), self::ERR_NO_OPERAND_UNARY));
	}

	public static function unexpectedToken(string $expression, Token $token) : self{
		return self::generateWithHighlightedSubstring(new self($expression, $token->getPos(), sprintf(
			"Unexpected %s token encountered at \"%s\" (%d:%d) in \"%s\"",
			$token->getType()->getName(),
			$token->getPos()->in($expression),
			$token->getPos()->getStart(),
			$token->getPos()->getEnd(),
			$expression
		), self::ERR_UNEXPECTED_TOKEN));
	}

	public static function unexpectedTokenWhenParsing(TokenBuilderState $state) : self{
		return self::generateWithHighlightedSubstring(new self($state->expression, new Position($state->offset, $state->length), sprintf(
			"Unexpected token encountered at (%d:%d) \"%s\" when parsing \"%s\"",
			$state->offset,
			$state->length,
			substr($state->expression, $state->offset, $state->length - $state->offset),
			$state->expression
		), self::ERR_UNEXPECTED_TOKEN));
	}

	private static function unresolvableFcall(string $expression, Position $position, string $reason, ?Throwable $previous = null) : self{
		return new self($expression, $position, sprintf(
			"Cannot resolve function call at \"%s\" (%d:%d) in \"%s\": %s",
			$position->in($expression),
			$position->getStart(),
			$position->getEnd(),
			$expression,
			$reason
		), self::ERR_UNRESOLVABLE_FCALL, $previous);
	}

	public static function unresolvableFcallGeneric(string $expression, Position $position, string $reason, ?Throwable $previous = null) : self{
		return self::generateWithHighlightedSubstring(self::unresolvableFcall($expression, $position, $reason, $previous));
	}

	public static function unresolvableFcallNoDefaultParamValue(string $expression, FunctionCallToken $token, int $parameter) : self{
		return self::generateWithHighlightedSubstring(self::unresolvableFcall($expression, $token->getPos(), sprintf("Function \"%s\" does not have a default value for parameter #%d", $token->getFunction(), $parameter)));
	}

	public static function unresolvableFcallTooLessParams(string $expression, Position $position, int $expected, int $params_c) : self{
		return self::generateWithHighlightedSubstring(self::unresolvableFcall($expression, $position, sprintf(
			"Too less parameters supplied to function call: Expected %d parameter%s, got %d parameter%s",
			$expected,
			$expected === 1 ? "" : "s",
			$params_c,
			$params_c === 1 ? "" : "s"
		)));
	}

	public static function unresolvableFcallTooManyParams(string $expression, Position $position, FunctionInfo $function, int $params_c) : self{
		return self::generateWithHighlightedSubstring(self::unresolvableFcall($expression, $position, sprintf(
			"Too many parameters supplied to function call: Expected %d parameter%s, got %d parameter%s",
			count($function->fallback_param_values),
			count($function->fallback_param_values) === 1 ? "" : "s",
			$params_c,
			$params_c === 1 ? "" : "s"
		)));
	}

	private static function unresolvableExpression(string $expression, Position $position, string $reason, ?Throwable $previous = null) : self{
		return new self($expression, $position, sprintf(
			"Cannot resolve expression at \"%s\" (%d:%d) in \"%s\": %s",
			$position->in($expression),
			$position->getStart(),
			$position->getEnd(),
			$expression,
			$reason
		), self::ERR_UNRESOLVABLE_EXPRESSION, $previous);
	}

	public static function unresolvableExpressionDivisionByZero(string $expression, Position $position) : self{
		return self::generateWithHighlightedSubstring(self::unresolvableExpression($expression, $position, "Division by zero"));
	}

	public static function unresolvableExpressionModuloByZero(string $expression, Position $position) : self{
		return self::generateWithHighlightedSubstring(self::unresolvableExpression($expression, $position, "Modulo by zero"));
	}

	public function __construct(
		private string $expression,
		private Position $position,
		string $message = "",
		int $code = 0,
		?Throwable $previous = null
	){
		parent::__construct($message, $code, $previous);
	}

	public function getExpression() : string{
		return $this->expression;
	}

	public function getPos() : Position{
		return $this->position;
	}
}