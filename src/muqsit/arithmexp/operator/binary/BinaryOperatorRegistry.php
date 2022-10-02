<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary;

use InvalidArgumentException;
use muqsit\arithmexp\operator\binary\assignment\BinaryOperatorAssignment;
use muqsit\arithmexp\operator\binary\assignment\LeftBinaryOperatorAssignment;
use muqsit\arithmexp\operator\binary\assignment\RightBinaryOperatorAssignment;
use muqsit\arithmexp\operator\ChangeListenableTrait;
use function array_key_first;
use function array_map;
use function array_unique;
use function count;
use function ksort;

final class BinaryOperatorRegistry{
	use ChangeListenableTrait;

	public static function createDefault() : self{
		$registry = new self();
		$registry->register(new SimpleBinaryOperator("+", "Addition", BinaryOperatorPrecedence::ADDITION_SUBTRACTION, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x + $y, true));
		$registry->register(new SimpleBinaryOperator("/", "Division", BinaryOperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x / $y, true));
		$registry->register(new SimpleBinaryOperator("**", "Exponential", BinaryOperatorPrecedence::EXPONENTIAL, RightBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x ** $y, true));
		$registry->register(new SimpleBinaryOperator("%", "Modulo", BinaryOperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int => $x % $y, true));
		$registry->register(new SimpleBinaryOperator("*", "Multiplication", BinaryOperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x * $y, true));
		$registry->register(new SimpleBinaryOperator("-", "Subtraction", BinaryOperatorPrecedence::ADDITION_SUBTRACTION, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x - $y, true));
		return $registry;
	}

	/** @var array<string, BinaryOperator> */
	private array $registered = [];

	/** @var BinaryOperatorList[] */
	private array $registered_by_precedence = [];

	public function __construct(){
	}

	public function register(BinaryOperator $operator) : void{
		$registered = $this->registered;
		$registered[$operator->getSymbol()] = $operator;
		$sorted_indexed = [];
		foreach($registered as $registered_operator){
			$sorted_indexed[$registered_operator->getPrecedence()][$registered_operator->getSymbol()] = $registered_operator;
		}
		ksort($sorted_indexed);

		$result = [];
		foreach($sorted_indexed as $list){
			$assignments = array_unique(array_map(static fn(BinaryOperator $operator) : int => $operator->getAssignment()->getType(), $list));
			if(count($assignments) > 1){
				throw new InvalidArgumentException("Cannot process binary operators of the same precedence but with different assignment types");
			}
			$result[] = new BinaryOperatorList(match($assignments[array_key_first($assignments)]){
				BinaryOperatorAssignment::TYPE_LEFT => LeftBinaryOperatorAssignment::instance(),
				BinaryOperatorAssignment::TYPE_RIGHT => RightBinaryOperatorAssignment::instance()
			}, $list);
		}

		$this->registered_by_precedence = $result;
		$this->registered = $registered;
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

	/**
	 * @return BinaryOperatorList[]
	 */
	public function getRegisteredByPrecedence() : array{
		return $this->registered_by_precedence;
	}
}