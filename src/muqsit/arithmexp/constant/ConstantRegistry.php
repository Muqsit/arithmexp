<?php

declare(strict_types=1);

namespace muqsit\arithmexp\constant;

use InvalidArgumentException;
use const INF;
use const M_1_PI;
use const M_2_PI;
use const M_2_SQRTPI;
use const M_E;
use const M_EULER;
use const M_LN10;
use const M_LN2;
use const M_LNPI;
use const M_LOG10E;
use const M_LOG2E;
use const M_PI;
use const M_PI_2;
use const M_PI_4;
use const M_SQRT1_2;
use const M_SQRT2;
use const M_SQRT3;
use const M_SQRTPI;
use const NAN;

final class ConstantRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->registerLabel("pi", M_PI);
		$registry->registerLabel("e", M_E);
		$registry->registerLabel("log2e", M_LOG2E);
		$registry->registerLabel("log10e", M_LOG10E);
		$registry->registerLabel("ln2", M_LN2);
		$registry->registerLabel("ln10", M_LN10);
		$registry->registerLabel("pi2", M_PI_2);
		$registry->registerLabel("pi4", M_PI_4);
		$registry->registerLabel("m_1pi", M_1_PI);
		$registry->registerLabel("m_2pi", M_2_PI);
		$registry->registerLabel("sqrtpi", M_SQRTPI);
		$registry->registerLabel("m_2sqrtpi", M_2_SQRTPI);
		$registry->registerLabel("sqrt2", M_SQRT2);
		$registry->registerLabel("sqrt3", M_SQRT3);
		$registry->registerLabel("sqrt12", M_SQRT1_2);
		$registry->registerLabel("lnpi", M_LNPI);
		$registry->registerLabel("euler", M_EULER);
		$registry->registerLabel("nan", NAN);
		$registry->registerLabel("inf", INF);
		return $registry;
	}

	/** @var array<string, ConstantInfo> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(string $identifier, ConstantInfo $info) : void{
		if(isset($this->registered[$identifier])){
			throw new InvalidArgumentException("Constant with the identifier \"{$identifier}\" is already registered");
		}

		$this->registered[$identifier] = $info;
	}

	public function unregister(string $identifier) : ConstantInfo{
		$info = $this->registered[$identifier] ?? throw new InvalidArgumentException("Constant with the identifier \"{$identifier}\" is not registered");
		unset($this->registered[$identifier]);
		return $info;
	}

	public function registerLabel(string $identifier, int|float $value) : void{
		$this->register($identifier, new SimpleConstantInfo($value));
	}

	public function get(string $identifier) : ConstantInfo{
		return $this->registered[$identifier] ?? throw new InvalidArgumentException("Constant \"{$identifier}\" is not registered");
	}

	/**
	 * @return array<string, ConstantInfo>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}
}
