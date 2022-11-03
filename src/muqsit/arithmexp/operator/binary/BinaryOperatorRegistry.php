<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary;

use InvalidArgumentException;
use muqsit\arithmexp\operator\assignment\LeftOperatorAssignment;
use muqsit\arithmexp\operator\assignment\RightOperatorAssignment;
use muqsit\arithmexp\operator\ChangeListenableTrait;
use muqsit\arithmexp\operator\OperatorPrecedence;

final class BinaryOperatorRegistry{
	use ChangeListenableTrait;

	public static function createDefault() : self{
		$registry = new self();
		$registry->register(new SimpleBinaryOperator("+", "Addition", OperatorPrecedence::ADDITION_SUBTRACTION, LeftOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x + $y, true, true));
		$registry->register(new SimpleBinaryOperator("/", "Division", OperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x / $y, false, true));
		$registry->register(new SimpleBinaryOperator("**", "Exponential", OperatorPrecedence::EXPONENTIAL, RightOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x ** $y, false, true));
		$registry->register(new SimpleBinaryOperator("%", "Modulo", OperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int => $x % $y, false, true));
		$registry->register(new SimpleBinaryOperator("*", "Multiplication", OperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x * $y, true, true));
		$registry->register(new SimpleBinaryOperator("-", "Subtraction", OperatorPrecedence::ADDITION_SUBTRACTION, LeftOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x - $y, false, true));
		return $registry;
	}

	/** @var array<string, BinaryOperator> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(BinaryOperator $operator) : void{
		$previous = $this->registered[$symbol = $operator->getSymbol()] ?? null;
		$this->registered[$symbol] = $operator;
		try{
			$this->notifyChangeHandler();
		}catch(InvalidArgumentException $e){
			if($previous === null){
				unset($this->registered[$symbol]);
			}else{
				$this->registered[$symbol] = $previous;
			}
			throw $e;
		}
		$this->notifyChangeListener();
	}

	public function get(string $symbol) : BinaryOperator{
		return $this->registered[$symbol] ?? throw new InvalidArgumentException("Binary operator \"{$symbol}\" is not registered");
	}

	/**
	 * @return array<string, BinaryOperator>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}
}