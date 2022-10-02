<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use InvalidArgumentException;
use muqsit\arithmexp\operator\ChangeListenableTrait;

final class UnaryOperatorRegistry{
	use ChangeListenableTrait;

	public static function createDefault() : self{
		$registry = new self();
		$registry->register(new SimpleUnaryOperator("+", "Positive", static fn(int|float $x) : int|float => +$x, true));
		$registry->register(new SimpleUnaryOperator("-", "Negative", static fn(int|float $x) : int|float => -$x, true));
		return $registry;
	}

	/** @var array<string, UnaryOperator> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(UnaryOperator $operator) : void{
		$this->registered[$operator->getSymbol()] = $operator;
		$this->notifyChangeListener();
	}

	public function get(string $symbol) : UnaryOperator{
		return $this->registered[$symbol] ?? throw new InvalidArgumentException("Unary operator \"{$symbol}\" is not registered");
	}

	/**
	 * @return array<string, UnaryOperator>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}
}