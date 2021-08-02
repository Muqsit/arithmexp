<?php

declare(strict_types=1);

namespace muqsit\arithmexp\tree;

final class NumericConstantTree implements Tree{

	public float $value;

	public function __construct(float $value){
		$this->value = $value;
	}

	public function getValue(array $variables) : float{
		return $this->value;
	}
}