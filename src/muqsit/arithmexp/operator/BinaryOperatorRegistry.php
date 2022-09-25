<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

use InvalidArgumentException;
use function array_key_first;
use function array_map;
use function count;
use function ksort;

final class BinaryOperatorRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->register(AdditionBinaryOperator::createDefault());
		$registry->register(DivisionBinaryOperator::createDefault());
		$registry->register(ExponentialBinaryOperator::createDefault());
		$registry->register(ModuloBinaryOperator::createDefault());
		$registry->register(MultiplicationBinaryOperator::createDefault());
		$registry->register(SubtractionBinaryOperator::createDefault());
		return $registry;
	}

	/** @var array<string, BinaryOperator> */
	private array $registered = [];

	/** @var BinaryOperatorList[] */
	private array $registered_by_precedence = [];

	public function __construct(){
	}

	public function register(BinaryOperator $operator) : void{
		$this->registered[$operator->getSymbol()] = $operator;
		$this->rebuildRegisteredByPrecedence();
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

	private function rebuildRegisteredByPrecedence() : void{
		$sorted_indexed = [];
		foreach($this->registered as $operator){
			$sorted_indexed[$operator->getPrecedence()][$operator->getSymbol()] = $operator;
		}
		ksort($sorted_indexed);

		$result = [];
		foreach($sorted_indexed as $list){
			$assignments = array_unique(array_map(static fn(BinaryOperator $operator) : int => $operator->getAssignmentType(), $list));
			if(count($assignments) > 1){
				throw new InvalidArgumentException("Cannot process binary operators of the same precedence but with different assignment types");
			}
			$result[] = new BinaryOperatorList($assignments[array_key_first($assignments)], $list);
		}

		$this->registered_by_precedence = $result;
	}
}