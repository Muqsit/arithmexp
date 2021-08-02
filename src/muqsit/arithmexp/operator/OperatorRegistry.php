<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

final class OperatorRegistry{

	public static function default() : self{
		$registry = new self();
		$registry->register("*", new MultiplicationOperator(), 0);
		$registry->register("/", new DivisionOperator(), 0);
		$registry->register("+", new AdditionOperator(), 1);
		$registry->register("-", new SubtractionOperator(), 1);
		return $registry;
	}

	/** @var array<string, Operator> */
	private array $operators = [];

	/** @var array<string, int> */
	private array $priorities = [];

	public function __construct(){
	}

	public function register(string $character, Operator $operator, int $priority) : void{
		$this->operators[$character] = $operator;
		$this->priorities[$character] = $priority;
	}

	public function get(string $character) : Operator{
		return $this->operators[$character];
	}

	/**
	 * @return string[]
	 */
	public function getCharacters() : array{
		return array_keys($this->operators);
	}

	/**
	 * @return array<string, Operator>
	 */
	public function getAll() : array{
		return $this->operators;
	}

	/**
	 * @return array<int, array<string, Operator>>
	 */
	public function getAllByPriority() : array{
		$by_priority = [];
		foreach($this->priorities as $character => $priority){
			$by_priority[$priority][$character] = $this->get($character);
		}
		ksort($by_priority);
		return $by_priority;
	}
}