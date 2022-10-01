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
		$registry->register(new SimpleBinaryOperator("+", "Addition", BinaryOperatorPrecedence::ADDITION_SUBTRACTION, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x + $y));
		$registry->register(new SimpleBinaryOperator("/", "Division", BinaryOperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x / $y));
		$registry->register(new SimpleBinaryOperator("**", "Exponential", BinaryOperatorPrecedence::EXPONENTIAL, RightBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x ** $y));
		$registry->register(new SimpleBinaryOperator("%", "Modulo", BinaryOperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int => $x % $y));
		$registry->register(new SimpleBinaryOperator("*", "Multiplication", BinaryOperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x * $y));
		$registry->register(new SimpleBinaryOperator("-", "Subtraction", BinaryOperatorPrecedence::ADDITION_SUBTRACTION, LeftBinaryOperatorAssignment::instance(), static fn(int|float $x, int|float $y) : int|float => $x - $y));
		return $registry;
	}

	/** @var array<string, BinaryOperator> */
	private array $registered = [];

	/** @var BinaryOperatorList[] */
	private array $registered_by_precedence = [];

	public function __construct(){
		$this->registerChangeListener(static function(BinaryOperatorRegistry $registry) : void{
			$sorted_indexed = [];
			foreach($registry->registered as $operator){
				$sorted_indexed[$operator->getPrecedence()][$operator->getSymbol()] = $operator;
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

			$registry->registered_by_precedence = $result;
		});
	}

	public function register(BinaryOperator $operator) : void{
		$this->registered[$operator->getSymbol()] = $operator;
		$this->notifyChangeListener();
	}

	public function get(string $symbol) : BinaryOperator{
		return $this->registered[$symbol] ?? throw new InvalidArgumentException("Operator \"{$symbol}\" is not registered");
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