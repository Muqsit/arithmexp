<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary;

use InvalidArgumentException;
use muqsit\arithmexp\function\FunctionFlags;
use muqsit\arithmexp\function\SimpleFunctionInfo;
use muqsit\arithmexp\macro\MacroFunctionInfo;
use muqsit\arithmexp\operator\assignment\LeftOperatorAssignment;
use muqsit\arithmexp\operator\assignment\NonAssociativeOperatorAssignment;
use muqsit\arithmexp\operator\assignment\RightOperatorAssignment;
use muqsit\arithmexp\operator\ChangeListenableTrait;
use muqsit\arithmexp\operator\OperatorPrecedence;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\OpcodeToken;
use muqsit\arithmexp\token\Token;

final class BinaryOperatorRegistry{
	use ChangeListenableTrait;

	public static function createDefault() : self{
		$registry = new self();
		$registry->register(new SimpleBinaryOperator("+", "Addition", OperatorPrecedence::ADDITION_SUBTRACTION, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float $x, int|float $y) : int|float => $x + $y, FunctionFlags::DETERMINISTIC | FunctionFlags::COMMUTATIVE),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_ADD, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("/", "Division", OperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float $x, int|float $y) : int|float => $x / $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_DIV, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("**", "Exponential", OperatorPrecedence::EXPONENTIAL, RightOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float $x, int|float $y) : int|float => $x ** $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_EXP, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("%", "Modulo", OperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float $x, int|float $y) : int => $x % $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_MOD, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("*", "Multiplication", OperatorPrecedence::MULTIPLICATION_DIVISION_MODULO, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float $x, int|float $y) : int|float => $x * $y, FunctionFlags::DETERMINISTIC | FunctionFlags::COMMUTATIVE),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_MUL, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("-", "Subtraction", OperatorPrecedence::ADDITION_SUBTRACTION, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float $x, int|float $y) : int|float => $x - $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_SUB, $token)]
		)));
		$registry->register(new SimpleBinaryOperator(">", "Greater than", OperatorPrecedence::COMPARISON_GREATER_LESSER, NonAssociativeOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x > $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_GREATER_THAN, $token)]
		)));
		$registry->register(new SimpleBinaryOperator(">=", "Greater than or equal to", OperatorPrecedence::COMPARISON_GREATER_LESSER, NonAssociativeOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x >= $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_GREATER_THAN_EQUAL_TO, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("<", "Lesser than", OperatorPrecedence::COMPARISON_GREATER_LESSER, NonAssociativeOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x < $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_LESSER_THAN, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("<=", "Lesser than or equal to", OperatorPrecedence::COMPARISON_GREATER_LESSER, NonAssociativeOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x <= $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_LESSER_THAN_EQUAL_TO, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("==", "Equal", OperatorPrecedence::COMPARISON_EQUALITY, NonAssociativeOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x == $y, FunctionFlags::DETERMINISTIC | FunctionFlags::COMMUTATIVE),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_EQUAL, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("!=", "Not equal", OperatorPrecedence::COMPARISON_EQUALITY, NonAssociativeOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x != $y, FunctionFlags::DETERMINISTIC | FunctionFlags::COMMUTATIVE),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_EQUAL_NOT, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("===", "Identical", OperatorPrecedence::COMPARISON_EQUALITY, NonAssociativeOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x === $y, FunctionFlags::DETERMINISTIC | FunctionFlags::COMMUTATIVE),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_IDENTICAL, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("!==", "Not identical", OperatorPrecedence::COMPARISON_EQUALITY, NonAssociativeOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x === $y, FunctionFlags::DETERMINISTIC | FunctionFlags::COMMUTATIVE),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_IDENTICAL_NOT, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("<=>", "Spaceship", OperatorPrecedence::COMPARISON_EQUALITY, NonAssociativeOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : int => $x <=> $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_SPACESHIP, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("&&", "And", OperatorPrecedence::AND_SYMBOL, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x && $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_AND_SYMBOL, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("||", "Or", OperatorPrecedence::OR_SYMBOL, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x || $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_OR_SYMBOL, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("and", "And", OperatorPrecedence::AND_TEXTUAL, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x and $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_AND_TEXTUAL, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("or", "Or", OperatorPrecedence::OR_TEXTUAL, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x or $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_OR_TEXTUAL, $token)]
		)));
		$registry->register(new SimpleBinaryOperator("xor", "Xor", OperatorPrecedence::XOR, LeftOperatorAssignment::instance(), new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float|bool $x, int|float|bool $y) : bool => $x xor $y, FunctionFlags::DETERMINISTIC),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_BINARY_XOR, $token)]
		)));
		return $registry;
	}

	/** @var array<string, BinaryOperator> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(BinaryOperator $operator) : void{
		$previous = $this->registered[$symbol = $operator->getSymbol()] ?? null;
		$this->registered[$symbol] = $operator;
		try{
			$this->notifyChangeHandler();
		}catch(InvalidArgumentException $e){
			if($previous === null){
				unset($this->registered[$symbol]);
			}else{
				$this->registered[$symbol] = $previous;
			}
			throw $e;
		}
		$this->notifyChangeListener();
	}

	public function get(string $symbol) : BinaryOperator{
		return $this->registered[$symbol] ?? throw new InvalidArgumentException("Binary operator \"{$symbol}\" is not registered");
	}

	/**
	 * @return array<string, BinaryOperator>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}
}