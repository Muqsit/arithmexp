<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

abstract class AdvancedOperator implements Operator{

	private ?float $left;
	private ?float $right;

	public function __construct(?float $left, ?float $right){
		$this->left = $left;
		$this->right = $right;
	}

	final public function getDefaultLeftValue() : ?float{
		return $this->left;
	}

	final public function getDefaultRightValue() : ?float{
		return $this->right;
	}
}