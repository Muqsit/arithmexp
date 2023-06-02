<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;
use muqsit\arithmexp\token\Token;
use ReflectionFunction;
use ReflectionParameter;
use function array_map;
use function gettype;
use function is_float;
use function is_int;

final class SimpleFunctionInfo implements FunctionInfo{

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
	 * @param list<int|float|null> $fallback_param_values
	 * @param bool $variadic
	 * @param int-mask-of<FunctionFlags::*> $flags
	 */
	public function __construct(
		readonly public Closure $closure,
		readonly public array $fallback_param_values,
		readonly public bool $variadic,
		readonly public int $flags
	){}

	public function getClosure() : Closure{
		return $this->closure;
	}

	public function getFallbackParamValues() : array{
		return $this->fallback_param_values;
	}

	public function isVariadic() : bool{
		return $this->variadic;
	}

	public function getFlags() : int{
		return $this->flags;
	}

	public function writeExpressionTokens(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, ExpressionTokenBuilderState $state) : void{
		$state->current_group[$state->current_index] = new FunctionCallExpressionToken($token->getPos(), $function_name, $argument_count, $this->closure, $this->flags, $token);
	}
}