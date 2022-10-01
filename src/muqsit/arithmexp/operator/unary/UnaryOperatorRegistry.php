<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use InvalidArgumentException;

final class UnaryOperatorRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->register(new SimpleUnaryOperator("+", "Positive", static fn(int|float $x) : int|float => +$x));
		$registry->register(new SimpleUnaryOperator("-", "Negative", static fn(int|float $x) : int|float => -$x));
		return $registry;
	}

	/** @var array<string, UnaryOperator> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(UnaryOperator $operator) : void{
		$this->registered[$operator->getSymbol()] = $operator;
	}

	public function get(string $symbol) : UnaryOperator{
		return $this->registered[$symbol] ?? throw new InvalidArgumentException("Operator \"{$symbol}\" is not registered");
	}

	/**
	 * @return array<string, UnaryOperator>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}
}