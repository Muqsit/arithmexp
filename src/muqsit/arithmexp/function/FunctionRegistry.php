<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;

final class FunctionRegistry{

	public static function createDefault() : self{
		$registry = new self();
		return $registry;
	}

	/** @var array<string, FunctionInfo> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(string $identifier, Closure $function) : void{
		$this->registered[$identifier] = FunctionInfo::from($function);
	}

	public function get(string $identifier) : FunctionInfo{
		return $this->registered[$identifier] ?? throw new InvalidArgumentException("Function \"{$identifier}\" is not registered");
	}
}