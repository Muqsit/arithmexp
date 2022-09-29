<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use ReflectionFunction;
use ReflectionParameter;
use function array_map;

final class FunctionInfo{

	public static function from(Closure $callback, bool $deterministic) : self{
		$_function = new ReflectionFunction($callback);
		return new self($callback, array_map(
			static fn(ReflectionParameter $_parameter) : int|float|null => $_parameter->isDefaultValueAvailable() ? $_parameter->getDefaultValue() : null,
			$_function->getParameters()
		), $_function->isVariadic(), $deterministic);
	}

	/**
	 * @param Closure $closure
	 * @param array<int|float|null> $fallback_param_values
	 * @param bool $deterministic
	 */
	public function __construct(
		public Closure $closure,
		public array $fallback_param_values,
		public bool $variadic,
		public bool $deterministic
	){}
}