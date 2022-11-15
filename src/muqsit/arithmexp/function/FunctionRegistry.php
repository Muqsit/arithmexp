<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;

final class FunctionRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->registerFunction("abs", Closure::fromCallable("abs"), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("acos", Closure::fromCallable("acos"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("acosh", Closure::fromCallable("acosh"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("asin", Closure::fromCallable("asin"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("asinh", Closure::fromCallable("asinh"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("atan2", Closure::fromCallable("atan2"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("atan", Closure::fromCallable("atan"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("atanh", Closure::fromCallable("atanh"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("ceil", Closure::fromCallable("ceil"), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("cos", Closure::fromCallable("cos"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("cosh", Closure::fromCallable("cosh"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("deg2rad", Closure::fromCallable("deg2rad"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("exp", Closure::fromCallable("exp"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("expm1", Closure::fromCallable("expm1"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("fdiv", Closure::fromCallable("fdiv"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("floor", Closure::fromCallable("floor"), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->registerFunction("fmod", Closure::fromCallable("fmod"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("hypot", Closure::fromCallable("hypot"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("intdiv", Closure::fromCallable("intdiv"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("lcg_value", Closure::fromCallable("lcg_value"));
		$registry->registerFunction("log10", Closure::fromCallable("log10"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("log1p", Closure::fromCallable("log1p"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("log", Closure::fromCallable("log"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("rad2deg", Closure::fromCallable("rad2deg"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("sin", Closure::fromCallable("sin"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("sinh", Closure::fromCallable("sinh"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("tan", Closure::fromCallable("tan"), FunctionFlags::DETERMINISTIC);
		$registry->registerFunction("tanh", Closure::fromCallable("tanh"), FunctionFlags::DETERMINISTIC);
		return $registry;
	}

	/** @var array<string, FunctionInfo> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(string $identifier, FunctionInfo $info) : void{
		$this->registered[$identifier] = $info;
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