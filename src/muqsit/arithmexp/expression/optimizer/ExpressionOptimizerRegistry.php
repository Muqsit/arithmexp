<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use InvalidArgumentException;
use muqsit\arithmexp\operator\ChangeListenableTrait;

final class ExpressionOptimizerRegistry{
	use ChangeListenableTrait;

	public static function createDefault() : self{
		$registry = new self();
		$registry->register("operator_reorder", new OperatorReorderExpressionOptimizer());
		$registry->register("constant_folding", new ConstantFoldingExpressionOptimizer());
		$registry->register("operator_strength_reduction", new OperatorStrengthReductionExpressionOptimizer());
		return $registry;
	}

	/** @var array<string, ExpressionOptimizer> */
	private array $registered = [];

	public function __construct(){
	}

	public function register(string $identifier, ExpressionOptimizer $optimizer) : void{
		$this->registered[$identifier] = $optimizer;
		$this->notifyChangeListener();
	}

	public function get(string $identifier) : ExpressionOptimizer{
		return $this->registered[$identifier] ?? throw new InvalidArgumentException("Expression optimizer \"{$identifier}\" is not registered");
	}

	/**
	 * @return array<string, ExpressionOptimizer>
	 */
	public function getRegistered() : array{
		return $this->registered;
	}
}