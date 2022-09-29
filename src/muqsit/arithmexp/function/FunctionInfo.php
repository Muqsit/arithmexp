<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use ReflectionFunction;

final class FunctionInfo{

	public static function from(Closure $callback, bool $deterministic) : self{
		$fallback_param_values = [];
		$_function = new ReflectionFunction($callback);
		foreach($_function->getParameters() as $_parameter){
			$fallback_param_values[] = $_parameter->isDefaultValueAvailable() ? $_parameter->getDefaultValue() : null;
		}
		return new self($callback, $fallback_param_values, $deterministic);
	}

	/**
	 * @param Closure $closure
	 * @param array<int|float|null> $fallback_param_values
	 * @param bool $deterministic
	 */
	public function __construct(
		public Closure $closure,
		public array $fallback_param_values,
		public bool $deterministic
	){}
}