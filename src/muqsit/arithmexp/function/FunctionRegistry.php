<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;

final class FunctionRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->register("abs", Closure::fromCallable("abs"));
		$registry->register("acos", Closure::fromCallable("acos"));
		$registry->register("acosh", Closure::fromCallable("acosh"));
		$registry->register("asin", Closure::fromCallable("asin"));
		$registry->register("asinh", Closure::fromCallable("asinh"));
		$registry->register("atan2", Closure::fromCallable("atan2"));
		$registry->register("atan", Closure::fromCallable("atan"));
		$registry->register("atanh", Closure::fromCallable("atanh"));
		$registry->register("ceil", Closure::fromCallable("ceil"));
		$registry->register("cos", Closure::fromCallable("cos"));
		$registry->register("cosh", Closure::fromCallable("cosh"));
		$registry->register("deg2rad", Closure::fromCallable("deg2rad"));
		$registry->register("exp", Closure::fromCallable("exp"));
		$registry->register("expm1", Closure::fromCallable("expm1"));
		$registry->register("fdiv", Closure::fromCallable("fdiv"));
		$registry->register("floor", Closure::fromCallable("floor"));
		$registry->register("fmod", Closure::fromCallable("fmod"));
		$registry->register("getrandmax", Closure::fromCallable("getrandmax"));
		$registry->register("hypot", Closure::fromCallable("hypot"));
		$registry->register("intdiv", Closure::fromCallable("intdiv"));
		$registry->register("lcg_value", Closure::fromCallable("lcg_value"));
		$registry->register("log10", Closure::fromCallable("log10"));
		$registry->register("log1p", Closure::fromCallable("log1p"));
		$registry->register("log", Closure::fromCallable("log"));
		$registry->register("max", Closure::fromCallable("max"));
		$registry->register("min", Closure::fromCallable("min"));
		$registry->register("mt_getrandmax", Closure::fromCallable("mt_getrandmax"));
		$registry->register("mt_rand", Closure::fromCallable("mt_rand"));
		$registry->register("mt_srand", Closure::fromCallable("mt_srand"));
		$registry->register("pi", Closure::fromCallable("pi"));
		$registry->register("pow", Closure::fromCallable("pow"));
		$registry->register("rad2deg", Closure::fromCallable("rad2deg"));
		$registry->register("rand", Closure::fromCallable("rand"));
		$registry->register("round", Closure::fromCallable("round"));
		$registry->register("sin", Closure::fromCallable("sin"));
		$registry->register("sinh", Closure::fromCallable("sinh"));
		$registry->register("sqrt", Closure::fromCallable("sqrt"));
		$registry->register("srand", Closure::fromCallable("srand"));
		$registry->register("tan", Closure::fromCallable("tan"));
		$registry->register("tanh", Closure::fromCallable("tanh"));
		return $registry;
	}

	/** @var array<string, FunctionInfo> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(string $identifier, Closure $function) : void{
		$this->registered[$identifier] = FunctionInfo::from($function);
	}

	public function get(string $identifier) : FunctionInfo{
		return $this->registered[$identifier] ?? throw new InvalidArgumentException("Function \"{$identifier}\" is not registered");
	}
}