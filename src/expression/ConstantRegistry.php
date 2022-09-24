<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use Closure;
use const M_E;
use const M_EULER;
use const M_LN10;
use const M_LN2;
use const M_LOG10E;
use const M_LOG2E;
use const M_PI;

final class ConstantRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->register("e", M_E);
		$registry->register("log2e", M_LOG2E);
		$registry->register("log10e", M_LOG10E);
		$registry->register("ln2", M_LN2);
		$registry->register("ln10", M_LN10);
		$registry->register("pi", M_PI);
		$registry->register("euler", M_EULER);
		return $registry;
	}

	/** @var array<string, int|float> */
	public array $registered = [];

	public function __construct(){
	}

	/**
	 * @param string $operator
	 * @param int|float $value
	 */
	public function register(string $operator, int|float $value) : void{
		$this->registered[$operator] = $value;
	}

	/**
	 * @return array<string, Closure(int|float, int|float) : int|float>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}
}