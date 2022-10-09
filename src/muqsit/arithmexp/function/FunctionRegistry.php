<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;
use function mt_rand;

final class FunctionRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->register("abs", Closure::fromCallable("abs"), true);
		$registry->register("acos", Closure::fromCallable("acos"), true);
		$registry->register("acosh", Closure::fromCallable("acosh"), true);
		$registry->register("asin", Closure::fromCallable("asin"), true);
		$registry->register("asinh", Closure::fromCallable("asinh"), true);
		$registry->register("atan2", Closure::fromCallable("atan2"), true);
		$registry->register("atan", Closure::fromCallable("atan"), true);
		$registry->register("atanh", Closure::fromCallable("atanh"), true);
		$registry->register("ceil", Closure::fromCallable("ceil"), true);
		$registry->register("cos", Closure::fromCallable("cos"), true);
		$registry->register("cosh", Closure::fromCallable("cosh"), true);
		$registry->register("deg2rad", Closure::fromCallable("deg2rad"), true);
		$registry->register("exp", Closure::fromCallable("exp"), true);
		$registry->register("expm1", Closure::fromCallable("expm1"), true);
		$registry->register("fdiv", Closure::fromCallable("fdiv"), true);
		$registry->register("floor", Closure::fromCallable("floor"), true);
		$registry->register("fmod", Closure::fromCallable("fmod"), true);
		$registry->register("getrandmax", Closure::fromCallable("getrandmax"), true);
		$registry->register("hypot", Closure::fromCallable("hypot"), true);
		$registry->register("intdiv", Closure::fromCallable("intdiv"), true);
		$registry->register("lcg_value", Closure::fromCallable("lcg_value"));
		$registry->register("log10", Closure::fromCallable("log10"), true);
		$registry->register("log1p", Closure::fromCallable("log1p"), true);
		$registry->register("log", Closure::fromCallable("log"), true);
		$registry->register("max", static fn(int|float $num1, int|float ...$nums) : int|float => max([$num1, ...$nums]), true, true);
		$registry->register("min", static fn(int|float $num1, int|float ...$nums) : int|float => min([$num1, ...$nums]), true, true);
		$registry->register("mt_getrandmax", Closure::fromCallable("mt_getrandmax"), true);
		$registry->register("mt_rand", static fn(int $min, int $max) : int => mt_rand($min, $max));
		$registry->register("pi", Closure::fromCallable("pi"), true);
		$registry->register("pow", Closure::fromCallable("pow"), true);
		$registry->register("rad2deg", Closure::fromCallable("rad2deg"), true);
		$registry->register("rand", Closure::fromCallable("rand"));
		$registry->register("round", Closure::fromCallable("round"), true);
		$registry->register("sin", Closure::fromCallable("sin"), true);
		$registry->register("sinh", Closure::fromCallable("sinh"), true);
		$registry->register("sqrt", Closure::fromCallable("sqrt"), true);
		$registry->register("tan", Closure::fromCallable("tan"), true);
		$registry->register("tanh", Closure::fromCallable("tanh"), true);
		return $registry;
	}

	/** @var array<string, FunctionInfo> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(string $identifier, Closure $function, bool $deterministic = false, bool $commutative = false) : void{
		$this->registered[$identifier] = FunctionInfo::from($function, $deterministic, $commutative);
	}

	public function get(string $identifier) : FunctionInfo{
		return $this->registered[$identifier] ?? throw new InvalidArgumentException("Function \"{$identifier}\" is not registered");
	}
}