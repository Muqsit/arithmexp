<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;

final class FunctionRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->registerFunction("abs", abs(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("acos", acos(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("acosh", acosh(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("asin", asin(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("asinh", asinh(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("atan2", atan2(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("atan", atan(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("atanh", atanh(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("boolval", boolval(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("ceil", ceil(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("cos", cos(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("cosh", cosh(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("deg2rad", deg2rad(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("exp", exp(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("expm1", expm1(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("fdiv", fdiv(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("floatval", floatval(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("floor", floor(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("fmod", fmod(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("hypot", hypot(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("intdiv", intdiv(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("intval", intval(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("is_bool", is_bool(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("is_float", is_float(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("is_finite", is_finite(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("is_infinite", is_infinite(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("is_nan", is_nan(...), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("lcg_value", lcg_value(...));
		$registry->registerFunction("log10", log10(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("log1p", log1p(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("log", log(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("rad2deg", rad2deg(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("sin", sin(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("sinh", sinh(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("tan", tan(...), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("tanh", tanh(...), FunctionFlags::DETERMINISTIC);
		return $registry;
	}

	/** @var array<string, FunctionInfo> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(string $identifier, FunctionInfo $info) : void{
		if(isset($this->registered[$identifier])){
			throw new InvalidArgumentException("Function with the identifier \"{$identifier}\" is already registered");
		}

		$this->registered[$identifier] = $info;
	}

	public function unregister(string $identifier) : FunctionInfo{
		$info = $this->registered[$identifier] ?? throw new InvalidArgumentException("Function with the identifier \"{$identifier}\" is not registered");
		unset($this->registered[$identifier]);
		return $info;
	}

	/**
	 * @param string $identifier
	 * @param Closure $function
	 * @param int-mask-of<FunctionFlags::*> $flags
	 */
	public function registerFunction(string $identifier, Closure $function, int $flags = 0) : void{
		$this->register($identifier, SimpleFunctionInfo::from($function, $flags));
	}

	public function get(string $identifier) : FunctionInfo{
		return $this->registered[$identifier] ?? throw new InvalidArgumentException("Function \"{$identifier}\" is not registered");
	}

	/**
	 * @return array<string, FunctionInfo>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}
}