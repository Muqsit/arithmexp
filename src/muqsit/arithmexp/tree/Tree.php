<?php

declare(strict_types=1);

namespace muqsit\arithmexp\tree;

interface Tree{

	/**
	 * @return array<Tree>
	 */
	public function getChildren() : array;

	/**
	 * @param array<string, float> $variables
	 * @return float
	 */
	public function getValue(array $variables) : float;
}