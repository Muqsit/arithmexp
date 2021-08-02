<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use muqsit\arithmexp\operator\OperatorRegistry;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\token\Tokenizer;
use muqsit\arithmexp\tree\RootTree;

final class ArithmeticExpression{

	private OperatorRegistry $operator_registry;
	private string $expression;
	private RootTree $tree;

	/** @var Token[] */
	private array $tokens;

	public function __construct(OperatorRegistry $operator_registry, string $expression){
		$this->operator_registry = $operator_registry;
		$this->expression = $expression;
		$tokenizer = new Tokenizer($this->operator_registry, $this->expression);
		$this->tokens = $tokenizer->tokenize();
		$this->tree = new RootTree($this);
	}

	public function getOperatorRegistry() : OperatorRegistry{
		return $this->operator_registry;
	}

	public function getExpression() : string{
		return $this->expression;
	}

	public function getTree() : RootTree{
		return $this->tree;
	}

	/**
	 * @return Token[]
	 */
	public function getTokens() : array{
		return $this->tokens;
	}

	/**
	 * @param array<string, float> $variables
	 * @return float
	 */
	public function getValue(array $variables = []) : float{
		return $this->tree->getValue($variables);
	}
}