<?php

declare(strict_types=1);

namespace muqsit\arithmexp\macro;

use Closure;
use muqsit\arithmexp\constant\ConstantInfo;
use muqsit\arithmexp\constant\ConstantRegistry;
use muqsit\arithmexp\function\FunctionFlags;
use muqsit\arithmexp\function\FunctionInfo;
use muqsit\arithmexp\function\FunctionRegistry;
use muqsit\arithmexp\function\SimpleFunctionInfo;
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
use function pow;
use const M_PI;
use const PHP_ROUND_HALF_DOWN;
use const PHP_ROUND_HALF_EVEN;
use const PHP_ROUND_HALF_ODD;
use const PHP_ROUND_HALF_UP;

final class MacroRegistry{
	
	public static function createDefault(ConstantRegistry $constant_registry, FunctionRegistry $function_registry) : self{
		$registry = new self($constant_registry, $function_registry);

		$registry->registerFunction("mt_rand", static function(int ...$args) : int{
			assert(count($args) === 0 || count($args) === 2);
			return mt_rand(...$args);
		}, static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array => $argument_count === 0 || $argument_count === 2 ? null : throw ($argument_count > 2 ?
			ParseException::unresolvableFcallTooManyParams($expression, $token->getPos(), $parser->getFunctionRegistry()->get($function_name), $argument_count) :
			ParseException::unresolvableFcallTooLessParams($expression, $token->getPos(), 2, $argument_count)
		));

		$registry->registerFunction("mt_getrandmax", Closure::fromCallable("mt_getrandmax"), static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [
			new NumericLiteralToken($token->getPos(), mt_getrandmax())
		], FunctionFlags::DETERMINISTIC);

		$registry->registerFunction("max", static function(int|float ...$nums) : int|float{
			assert(count($nums) >= 2);
			return max($nums);
		}, static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array => match($argument_count){
			0 => throw ParseException::unresolvableFcallTooLessParams($expression, $token->getPos(), 1, 0),
			1 => [$args[0]],
			default => null
		}, FunctionFlags::COMMUTATIVE | FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);

		$registry->registerFunction("min", static function(int|float ...$nums) : int|float{
			assert(count($nums) >= 2);
			return min($nums);
		}, static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array => match($argument_count){
			0 => throw ParseException::unresolvableFcallTooLessParams($expression, $token->getPos(), 1, 0),
			1 => [$args[0]],
			default => null
		}, FunctionFlags::COMMUTATIVE | FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT);

		$registry->registerFunction("pi", static fn() : float => M_PI, static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [
			new NumericLiteralToken($token->getPos(), M_PI)
		], FunctionFlags::DETERMINISTIC);

		$registry->registerFunction("pow", static fn(int|float $base, int|float $exponent) : int|float => pow($base, $exponent), static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [
			$args[0],
			$args[1],
			new BinaryOperatorToken($token->getPos(), "**")
		], FunctionFlags::DETERMINISTIC);

		$registry->registerFunction("round", Closure::fromCallable("round"), static function(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : ?array{
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

		$registry->registerFunction("sqrt", Closure::fromCallable("sqrt"), static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [
			$args[0],
			new NumericLiteralToken($token->getPos(), 0.5),
			new BinaryOperatorToken($token->getPos(), "**")
		], FunctionFlags::DETERMINISTIC);
		return $registry;
	}

	public function __construct(
		private ConstantRegistry $constant_registry,
		private FunctionRegistry $function_registry
	){}

	/**
	 * @param string $identifier
	 * @param Closure $base
	 * @param Closure(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, list<Token|list<Token>> $args) : (list<Token>|null) $resolver
	 * @param int-mask-of<FunctionFlags::*> $flags
	 */
	public function registerFunction(string $identifier, Closure $base, Closure $resolver, int $flags = 0) : void{
		$this->function_registry->register($identifier, new MacroFunctionInfo(SimpleFunctionInfo::from($base, $flags), $resolver));
	}

	/**
	 * @param string $identifier
	 * @param Closure(Parser $parser, string $expression, IdentifierToken $token) : list<Token> $resolver
	 */
	public function registerObject(string $identifier, Closure $resolver) : void{
		$this->constant_registry->register($identifier, new MacroConstantInfo($resolver));
	}

	public function unregisterFunction(string $identifier) : FunctionInfo{
		return $this->function_registry->unregister($identifier);
	}

	public function unregisterObject(string $identifier) : ConstantInfo{
		return $this->constant_registry->unregister($identifier);
	}
}