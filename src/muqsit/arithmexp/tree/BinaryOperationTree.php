<?php

declare(strict_types=1);

namespace muqsit\arithmexp\tree;

use muqsit\arithmexp\operator\Operator;

final class BinaryOperationTree implements Tree{

	private Operator $operator;
	private Tree $left;
	private Tree $right;

	public function __construct(Operator $operator, Tree $left, Tree $right){
		$this->operator = $operator;
		$this->left = $left;
		$this->right = $right;
	}

	public function getValue(array $variables) : float{
		return $this->operator->getValue($this->left->getValue($variables), $this->right->getValue($variables));
	}
}