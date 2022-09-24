<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use Closure;
use InvalidArgumentException;
use muqsit\arithmexp\token\BinaryOperatorToken;

final class BinaryOperatorRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->register(BinaryOperatorToken::OPERATOR_TYPE_EXPONENTIAL, static fn(int|float $x, int|float $y) : int|float => $x ** $y);
		$registry->register(BinaryOperatorToken::OPERATOR_TYPE_MODULO, static fn(int|float $x, int|float $y) : int => $x % $y);
		$registry->register(BinaryOperatorToken::OPERATOR_TYPE_DIVISION, static fn(int|float $x, int|float $y) : int|float => $x / $y);
		$registry->register(BinaryOperatorToken::OPERATOR_TYPE_MULTIPLICATION, static fn(int|float $x, int|float $y) : int|float => $x * $y);
		$registry->register(BinaryOperatorToken::OPERATOR_TYPE_ADDITION, static fn(int|float $x, int|float $y) : int|float => $x + $y);
		$registry->register(BinaryOperatorToken::OPERATOR_TYPE_SUBTRACTION, static fn(int|float $x, int|float $y) : int|float => $x - $y);
		return $registry;
	}

	/** @var array<string, Closure(int|float, int|float) : (int|float)> */
	private array $registered = [];

	public function __construct(){
	}

	/**
	 * @param string $operator
	 * @param Closure(int|float, int|float) : (int|float) $operation
	 */
	public function register(string $operator, Closure $operation) : void{
		$this->registered[$operator] = $operation;
	}

	/**
	 * @return array<string, Closure(int|float, int|float) : (int|float)>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}

	public function evaluate(string $operator, int|float $left, int|float $right) : int|float{
		$operation = $this->registered[$operator] ?? throw new InvalidArgumentException("Don't know how to operate with operator \"{$operator}\"");
		return $operation($left, $right);
	}
}