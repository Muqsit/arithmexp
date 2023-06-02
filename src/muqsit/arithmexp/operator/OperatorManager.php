<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

use InvalidArgumentException;
use muqsit\arithmexp\operator\assignment\LeftOperatorAssignment;
use muqsit\arithmexp\operator\assignment\NullOperatorAssignment;
use muqsit\arithmexp\operator\assignment\OperatorAssignment;
use muqsit\arithmexp\operator\assignment\RightOperatorAssignment;
use muqsit\arithmexp\operator\binary\BinaryOperator;
use muqsit\arithmexp\operator\binary\BinaryOperatorRegistry;
use muqsit\arithmexp\operator\unary\UnaryOperator;
use muqsit\arithmexp\operator\unary\UnaryOperatorRegistry;

final class OperatorManager{

	public static function createDefault() : self{
		return new self(
			BinaryOperatorRegistry::createDefault(),
			UnaryOperatorRegistry::createDefault()
		);
	}

	/** @var list<OperatorList>|null */
	private ?array $by_precedence_cached = null;

	public function __construct(
		readonly private BinaryOperatorRegistry $binary_registry,
		readonly private UnaryOperatorRegistry $unary_registry
	){
		$this->binary_registry->registerChangeHandler(function(BinaryOperatorRegistry $_) : void{
			$this->sortedByPrecedence();
		});
		$this->unary_registry->registerChangeHandler(function(UnaryOperatorRegistry $_) : void{
			$this->sortedByPrecedence();
		});
		$this->binary_registry->registerChangeListener(function(BinaryOperatorRegistry $_) : void{
			$this->by_precedence_cached = null;
		});
		$this->unary_registry->registerChangeListener(function(UnaryOperatorRegistry $_) : void{
			$this->by_precedence_cached = null;
		});
		$this->getByPrecedence(); // init by_precedence_cache
	}

	public function getBinaryRegistry() : BinaryOperatorRegistry{
		return $this->binary_registry;
	}

	public function getUnaryRegistry() : UnaryOperatorRegistry{
		return $this->unary_registry;
	}

	/**
	 * @return list<OperatorList>
	 */
	public function getByPrecedence() : array{
		return $this->by_precedence_cached ??= $this->sortedByPrecedence();
	}

	/**
	 * @return list<OperatorList>
	 */
	private function sortedByPrecedence() : array{
		$sorted = [];
		foreach($this->binary_registry->getRegistered() as $operator){
			$sorted[$operator->getPrecedence()][spl_object_id($operator)] = $operator;
		}
		foreach($this->unary_registry->getRegistered() as $operator){
			$sorted[$operator->getPrecedence()][spl_object_id($operator)] = $operator;
		}
		ksort($sorted);

		$result = [];
		foreach($sorted as $list){
			$assignments = array_unique(array_map(static fn(BinaryOperator|UnaryOperator $operator) : int => $operator instanceof BinaryOperator ? $operator->getAssignment()->getType() : OperatorAssignment::TYPE_NA, $list));
			if(count($assignments) > 1){
				throw new InvalidArgumentException("Cannot process operators with same precedence ({$operator->getPrecedence()}) but different assignment types (" . implode(", ", $assignments) . ")");
			}

			$binary = [];
			$unary = [];
			foreach($list as $operator){
				match(true){
					$operator instanceof BinaryOperator => $binary[$operator->getSymbol()] = $operator,
					$operator instanceof UnaryOperator => $unary[$operator->getSymbol()] = $operator
				};
			}

			$result[] = new OperatorList(match($assignments[array_key_first($assignments)]){
				OperatorAssignment::TYPE_LEFT => LeftOperatorAssignment::instance(),
				OperatorAssignment::TYPE_RIGHT => RightOperatorAssignment::instance(),
				OperatorAssignment::TYPE_NA => NullOperatorAssignment::instance()
			}, $binary, $unary);
		}
		return $result;
	}
}