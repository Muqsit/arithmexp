<?php

declare(strict_types=1);

namespace muqsit\arithmexp\tree;

interface Tree{

	/**
	 * @param array<string, float> $variables
	 * @return float
	 */
	public function getValue(array $variables) : float;
}