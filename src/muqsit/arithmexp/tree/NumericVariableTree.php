<?php

declare(strict_types=1);

namespace muqsit\arithmexp\tree;

use muqsit\arithmexp\util\ArithmeticExpressionException;

final class NumericVariableTree implements Tree{

	public string $identifier;

	public function __construct(string $identifier){
		$this->identifier = $identifier;
	}

	public function getVariableIdentifier() : string{
		return $this->identifier;
	}

	public function getChildren() : array{
		return [];
	}

	public function getValue(array $variables) : float{
		return $variables[$this->identifier] ?? throw new ArithmeticExpressionException("Value of variable '{$this->identifier}' not specified");
	}
}