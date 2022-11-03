<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionParameter;
use function array_map;
use function gettype;
use function is_float;
use function is_int;

final class FunctionInfo{

	/**
	 * @param Closure $callback
	 * @param int-mask-of<FunctionFlags::*> $flags
	 * @return self
	 */
	public static function from(Closure $callback, int $flags) : self{
		$_function = new ReflectionFunction($callback);
		return new self($callback, array_map(static function(ReflectionParameter $_parameter) : int|float|null{
			if($_parameter->isDefaultValueAvailable()){
				$value = $_parameter->getDefaultValue();
				if(!is_int($value) && !is_float($value)){
					throw new InvalidArgumentException("Expected default parameter value to be int|float, got " . gettype($value) . " for parameter \"{$_parameter->getName()}\"");
				}
				return $value;
			}
			return null;
		}, $_function->getParameters()), $_function->isVariadic(), $flags);
	}

	/**
	 * @param Closure $closure
	 * @param array<int|float|null> $fallback_param_values
	 * @param int-mask-of<FunctionFlags::*> $flags
	 */
	public function __construct(
		public Closure $closure,
		public array $fallback_param_values,
		public bool $variadic,
		public int $flags
	){}
}