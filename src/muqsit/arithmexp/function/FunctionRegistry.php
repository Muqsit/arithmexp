<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\IdentifierToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\Token;
use function assert;
use function count;
use function max;
use function min;
use function mt_getrandmax;
use function mt_rand;
use const PHP_ROUND_HALF_DOWN;
use const PHP_ROUND_HALF_EVEN;
use const PHP_ROUND_HALF_ODD;
use const PHP_ROUND_HALF_UP;

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

		$registry->registerMacro("mt_rand", static function(int ...$args) : int{
			assert(count($args) === 0 || count($args) === 2);
			return mt_rand(...$args);
		}, static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array => $argument_count === 0 || $argument_count === 2 ? null : throw ($argument_count > 2 ?
			ParseException::unresolvableFcallTooManyParams($expression, $token->getPos(), $parser->getFunctionRegistry()->get($function_name), $argument_count) :
			ParseException::unresolvableFcallTooLessParams($expression, $token->getPos(), 2, $argument_count)
		));

		$registry->registerMacro("mt_getrandmax", Closure::fromCallable("mt_getrandmax"), static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [
			new NumericLiteralToken($token->getPos(), mt_getrandmax())
		], FunctionFlags::DETERMINISTIC);

		$registry->registerMacro("max", static function(int|float ...$nums) : int|float{
			assert(count($nums) >= 2);
			return max($nums);
		}, static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array => match($argument_count){
			0 => throw ParseException::unresolvableFcallTooLessParams($expression, $token->getPos(), 1, 0),
			1 => [$args[0]],
			default => null
		}, FunctionFlags::COMMUTATIVE | FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);

		$registry->registerMacro("min", static function(int|float ...$nums) : int|float{
			assert(count($nums) >= 2);
			return min($nums);
		}, static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array => match($argument_count){
			0 => throw ParseException::unresolvableFcallTooLessParams($expression, $token->getPos(), 1, 0),
			1 => [$args[0]],
			default => null
		}, FunctionFlags::COMMUTATIVE | FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);

		$registry->registerMacro("pi", static fn() : float => M_PI, static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [
			new NumericLiteralToken($token->getPos(), M_PI)
		], FunctionFlags::DETERMINISTIC);

		$registry->registerMacro("pow", static fn(int|float $base, int|float $exponent) : int|float => pow($base, $exponent), static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [
			$args[0],
			$args[1],
			new BinaryOperatorToken($token->getPos(), "**")
		], FunctionFlags::DETERMINISTIC);

		$registry->registerMacro("round", Closure::fromCallable("round"), static function(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array{
			if($argument_count !== 3){
				return null;
			}
			$argument = $args[2];
			if(!($argument instanceof IdentifierToken)){
				return null;
			}
			$replacement = match($argument->getLabel()){
				"HALF_UP" => PHP_ROUND_HALF_UP,
				"HALF_DOWN" => PHP_ROUND_HALF_DOWN,
				"HALF_EVEN" => PHP_ROUND_HALF_EVEN,
				"HALF_ODD" => PHP_ROUND_HALF_ODD,
				default => null
			};
			if($replacement === null){
				return null;
			}
			return [$args[0], $args[1], new NumericLiteralToken($argument->getPos(), $replacement), $token];
		}, FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);

		$registry->registerMacro("sqrt", Closure::fromCallable("sqrt"), static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [
			$args[0],
			new NumericLiteralToken($token->getPos(), 0.5),
			new BinaryOperatorToken($token->getPos(), "**")
		], FunctionFlags::DETERMINISTIC);
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

	/**
	 * @param string $identifier
	 * @param Closure $base
	 * @param Closure(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, list<Token|list<Token>> $args) : (list<Token>|null) $resolver
	 * @param int-mask-of<FunctionFlags::*> $flags
	 */
	public function registerMacro(string $identifier, Closure $base, Closure $resolver, int $flags = 0) : void{
		$this->register($identifier, new MacroFunctionInfo(SimpleFunctionInfo::from($base, $flags), $resolver));
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