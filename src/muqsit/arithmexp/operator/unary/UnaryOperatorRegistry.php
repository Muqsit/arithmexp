<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\unary;

use InvalidArgumentException;
use muqsit\arithmexp\function\FunctionFlags;
use muqsit\arithmexp\function\SimpleFunctionInfo;
use muqsit\arithmexp\macro\MacroFunctionInfo;
use muqsit\arithmexp\operator\ChangeListenableTrait;
use muqsit\arithmexp\operator\OperatorPrecedence;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\OpcodeToken;
use muqsit\arithmexp\token\Token;

final class UnaryOperatorRegistry{
	use ChangeListenableTrait;

	public static function createDefault() : self{
		$registry = new self();
		$registry->register(new SimpleUnaryOperator("+", "Positive", OperatorPrecedence::UNARY_NEGATIVE_POSITIVE, new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float $x) : int|float => +$x, FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_UNARY_PVE, $token)]
		)));
		$registry->register(new SimpleUnaryOperator("-", "Negative", OperatorPrecedence::UNARY_NEGATIVE_POSITIVE, new MacroFunctionInfo(
			SimpleFunctionInfo::from(static fn(int|float $x) : int|float => -$x, FunctionFlags::DETERMINISTIC | FunctionFlags::IDEMPOTENT),
			static fn(Parser $parser, string $expression, Token $token, string $function_name, int $argument_count, array $args) : array => [...$args, new OpcodeToken($token->getPos(), OpcodeToken::OP_UNARY_NVE, $token)]
		)));
		return $registry;
	}

	/** @var array<string, UnaryOperator> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(UnaryOperator $operator) : void{
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

	public function get(string $symbol) : UnaryOperator{
		return $this->registered[$symbol] ?? throw new InvalidArgumentException("Unary operator \"{$symbol}\" is not registered");
	}

	/**
	 * @return array<string, UnaryOperator>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}
}