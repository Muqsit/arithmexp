<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\FunctionCallToken;
use muqsit\arithmexp\token\Token;
use function max;
use function min;
use function mt_rand;

final class FunctionRegistry{

	public static function createDefault() : self{
		$registry = new self();
		$registry->register("abs", Closure::fromCallable("abs"), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->register("acos", Closure::fromCallable("acos"), FunctionFlags::DETERMINISTIC);
		$registry->register("acosh", Closure::fromCallable("acosh"), FunctionFlags::DETERMINISTIC);
		$registry->register("asin", Closure::fromCallable("asin"), FunctionFlags::DETERMINISTIC);
		$registry->register("asinh", Closure::fromCallable("asinh"), FunctionFlags::DETERMINISTIC);
		$registry->register("atan2", Closure::fromCallable("atan2"), FunctionFlags::DETERMINISTIC);
		$registry->register("atan", Closure::fromCallable("atan"), FunctionFlags::DETERMINISTIC);
		$registry->register("atanh", Closure::fromCallable("atanh"), FunctionFlags::DETERMINISTIC);
		$registry->register("ceil", Closure::fromCallable("ceil"), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->register("cos", Closure::fromCallable("cos"), FunctionFlags::DETERMINISTIC);
		$registry->register("cosh", Closure::fromCallable("cosh"), FunctionFlags::DETERMINISTIC);
		$registry->register("deg2rad", Closure::fromCallable("deg2rad"), FunctionFlags::DETERMINISTIC);
		$registry->register("exp", Closure::fromCallable("exp"), FunctionFlags::DETERMINISTIC);
		$registry->register("expm1", Closure::fromCallable("expm1"), FunctionFlags::DETERMINISTIC);
		$registry->register("fdiv", Closure::fromCallable("fdiv"), FunctionFlags::DETERMINISTIC);
		$registry->register("floor", Closure::fromCallable("floor"), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->register("fmod", Closure::fromCallable("fmod"), FunctionFlags::DETERMINISTIC);
		$registry->register("getrandmax", Closure::fromCallable("getrandmax"), FunctionFlags::DETERMINISTIC);
		$registry->register("hypot", Closure::fromCallable("hypot"), FunctionFlags::DETERMINISTIC);
		$registry->register("intdiv", Closure::fromCallable("intdiv"), FunctionFlags::DETERMINISTIC);
		$registry->register("lcg_value", Closure::fromCallable("lcg_value"));
		$registry->register("log10", Closure::fromCallable("log10"), FunctionFlags::DETERMINISTIC);
		$registry->register("log1p", Closure::fromCallable("log1p"), FunctionFlags::DETERMINISTIC);
		$registry->register("log", Closure::fromCallable("log"), FunctionFlags::DETERMINISTIC);
		$registry->register("max", static fn(int|float $num1, int|float ...$nums) : int|float => max([$num1, ...$nums]), FunctionFlags::COMMUTATIVE | FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->register("min", static fn(int|float $num1, int|float ...$nums) : int|float => min([$num1, ...$nums]), FunctionFlags::COMMUTATIVE | FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->register("mt_getrandmax", Closure::fromCallable("mt_getrandmax"), FunctionFlags::DETERMINISTIC);
		$registry->register("mt_rand", static fn(int $min, int $max) : int => mt_rand($min, $max));
		$registry->register("pi", Closure::fromCallable("pi"), FunctionFlags::DETERMINISTIC);
		$registry->register("pow", Closure::fromCallable("pow"), FunctionFlags::DETERMINISTIC);
		$registry->register("rad2deg", Closure::fromCallable("rad2deg"), FunctionFlags::DETERMINISTIC);
		$registry->register("rand", Closure::fromCallable("rand"));
		$registry->register("round", Closure::fromCallable("round"), FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);
		$registry->register("sin", Closure::fromCallable("sin"), FunctionFlags::DETERMINISTIC);
		$registry->register("sinh", Closure::fromCallable("sinh"), FunctionFlags::DETERMINISTIC);
		$registry->register("sqrt", Closure::fromCallable("sqrt"), FunctionFlags::DETERMINISTIC);
		$registry->register("tan", Closure::fromCallable("tan"), FunctionFlags::DETERMINISTIC);
		$registry->register("tanh", Closure::fromCallable("tanh"), FunctionFlags::DETERMINISTIC);
		return $registry;
	}

	/** @var array<string, FunctionInfo> */
	private array $registered = [];

	public function __construct(){
	}

	/**
	 * @param string $identifier
	 * @param Closure $function
	 * @param int-mask-of<FunctionFlags::*> $flags
	 */
	public function register(string $identifier, Closure $function, int $flags = 0) : void{
		$this->registered[$identifier] = SimpleFunctionInfo::from($function, $flags);
	}

	/**
	 * @param string $identifier
	 * @param Closure $base
	 * @param Closure(Parser $parser, Expression $expression, FunctionCallToken $token, Token[]|Token[][] $args) : (Token[]|null) $resolver
	 * @param int-mask-of<FunctionFlags::*> $flags
	 */
	public function registerMacro(string $identifier, Closure $base, Closure $resolver, int $flags = 0) : void{
		$this->registered[$identifier] = new MacroFunctionInfo(SimpleFunctionInfo::from($base, $flags), $resolver);
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