<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use Closure;
use const M_PI;
use const M_E;
use const M_LOG2E;
use const M_LOG10E;
use const M_LN2;
use const M_LN10;
use const M_PI_2;
use const M_PI_4;
use const M_1_PI;
use const M_2_PI;
use const M_SQRTPI;
use const M_2_SQRTPI;
use const M_SQRT2;
use const M_SQRT3;
use const M_SQRT1_2;
use const M_LNPI;
use const M_EULER;
use const NAN;
use const INF;

final class ConstantRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->register("pi", M_PI);
		$registry->register("e", M_E);
		$registry->register("log2e", M_LOG2E);
		$registry->register("log10e", M_LOG10E);
		$registry->register("ln2", M_LN2);
		$registry->register("ln10", M_LN10);
		$registry->register("pi2", M_PI_2);
		$registry->register("pi4", M_PI_4);
		$registry->register("m_1pi", M_1_PI);
		$registry->register("m_2pi", M_2_PI);
		$registry->register("sqrtpi", M_SQRTPI);
		$registry->register("m_2sqrtpi", M_2_SQRTPI);
		$registry->register("sqrt2",  M_SQRT2);
		$registry->register("sqrt3",  M_SQRT3);
		$registry->register("sqrt12",  M_SQRT1_2);
		$registry->register("lnpi", M_LNPI);
		$registry->register("euler", M_EULER);
		$registry->register("nan", NAN);
		$registry->register("inf", INF);
		return $registry;
	}

	/** @var array<string, int|float> */
	public array $registered = [];

	public function __construct(){
	}

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
