<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use InvalidArgumentException;
use muqsit\arithmexp\expression\ConstantRegistry;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\function\FunctionRegistry;
use muqsit\arithmexp\operator\BinaryOperatorAssignmentType;
use muqsit\arithmexp\operator\BinaryOperatorRegistry;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\FunctionCallArgumentSeparatorToken;
use muqsit\arithmexp\token\FunctionCallToken;
use muqsit\arithmexp\token\IdentifierToken;
use muqsit\arithmexp\token\LeftParenthesisToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\RightParenthesisToken;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\token\UnaryOperatorToken;
use RuntimeException;
use function array_key_last;
use function array_map;
use function array_shift;
use function array_splice;
use function array_unshift;
use function count;
use function is_array;
use function substr;

final class Parser{

	public static function createDefault() : self{
		$binary_operator_registry = BinaryOperatorRegistry::createDefault();
		return new self(
			$binary_operator_registry,
			ConstantRegistry::createDefault(),
			FunctionRegistry::createDefault(),
			Scanner::createDefault($binary_operator_registry)
		);
	}

	public function __construct(
		private BinaryOperatorRegistry $binary_operator_registry,
		private ConstantRegistry $constant_registry,
		private FunctionRegistry $function_registry,
		private Scanner $scanner
	){}

	public function getBinaryOperatorRegistry() : BinaryOperatorRegistry{
		return $this->binary_operator_registry;
	}

	public function getConstantRegistry() : ConstantRegistry{
		return $this->constant_registry;
	}

	/**
	 * Parses a given mathematical expression for runtime evaluation.
	 *
	 * @param string $expression
	 * @return Expression
	 */
	public function parse(string $expression) : Expression{
		$tokens = $this->scanner->scan($expression);
		$this->deparenthesizeTokens($tokens);
		$this->transformFunctionCallTokens($expression, $tokens);
		$this->groupUnaryOperatorTokens($tokens);
		$this->groupBinaryOperations($tokens);
		$this->convertTokenTreeToPostfixTokenTree($expression, $tokens);
		return new Expression($expression, array_map(function(Token $token) : ExpressionToken{
			if($token instanceof BinaryOperatorToken){
				$operator = $this->binary_operator_registry->get($token->getOperator());
				return new FunctionCallExpressionToken("BO<{$operator->getSymbol()}>", 2, $operator->getOperator());
			}
			if($token instanceof UnaryOperatorToken){
				return new FunctionCallExpressionToken("UO<{$token->getOperator()}>", 1, match($token->getOperator()){
					UnaryOperatorToken::OPERATOR_TYPE_NEGATIVE => static fn(int|float $x) : int|float => -$x,
					UnaryOperatorToken::OPERATOR_TYPE_POSITIVE => static fn(int|float $x) : int|float => +$x
				});
			}
			if($token instanceof FunctionCallToken){
				$name = $token->getFunction();
				$function = $this->function_registry->get($name);
				return new FunctionCallExpressionToken($name, count($function->fallback_param_values), $function->closure);
			}
			if($token instanceof NumericLiteralToken){
				return new NumericLiteralExpressionToken($token->getValue());
			}
			if($token instanceof IdentifierToken){
				$label = $token->getLabel();
				$constant_value = $this->constant_registry->registered[$label] ?? null;
				return $constant_value !== null ? new NumericLiteralExpressionToken($constant_value) : new VariableExpressionToken($label);
			}
			throw new RuntimeException("Don't know how to convert {$token->getType()->getName()} token to " . ExpressionToken::class);
		}, $tokens));
	}

	/**
	 * Transforms a given token tree in-place by removing parenthesis tokens
	 * {@see LeftParenthesisToken, RightParenthesisToken} by introducing nesting.
	 *
	 * This transforms [LP, NUM, OP, NUM, RP, OP, NUM] to [[NUM, OP, NUM], OP, NUM].
	 *
	 * @param Token[] $tokens
	 */
	private function deparenthesizeTokens(array &$tokens) : void{
		for($i = count($tokens) - 1; $i >= 0; --$i){
			$token = $tokens[$i];
			if(!($token instanceof LeftParenthesisToken)){
				continue;
			}

			/** @var Token[] $group */
			$group = [];
			$j = $i + 1;
			while(!($tokens[$j] instanceof RightParenthesisToken)){
				$group[] = $tokens[$j++];
			}

			array_splice($tokens, $i, 1 + ($j - $i), match(count($group)){
				0 => [],
				1 => $group,
				default => [$group]
			});
			$i = count($tokens) - 1;
		}
	}

	/**
	 * Transforms a given token tree in-place by grouping {@see UnaryOperatorToken}
	 * instances together with its operand.
	 *
	 * @param Token[]|Token[][] $tokens
	 */
	private function groupUnaryOperatorTokens(array &$tokens) : void{
		$stack = [&$tokens];
		while(($index = array_key_last($stack)) !== null){
			$entry = &$stack[$index];
			unset($stack[$index]);
			for($i = count($entry) - 1; $i >= 0; --$i){
				$token = $entry[$i];
				if(!($token instanceof UnaryOperatorToken)){
					if(is_array($token)){
						$stack[] = &$entry[$i];
					}
					continue;
				}

				array_splice($entry, $i, 2, [[$token, $entry[$i + 1]]]);
			}
		}
	}

	/**
	 * Transforms a given token tree in-place by grouping all binary operations for
	 * low-complexity processing, converting [TOK, BOP, TOK, BOP, TOK] to
	 * [[[TOK, BOP, TOK], BOP, TOK]].
	 *
	 * @param Token[]|Token[][] $tokens
	 */
	private function groupBinaryOperations(array &$tokens) : void{
		foreach($tokens as $i => $value){
			if(is_array($value)){
				foreach($value as $token){
					if($token instanceof BinaryOperatorToken){
						$this->groupBinaryOperations($tokens[$i]);
						break;
					}
				}
			}
		}

		foreach($this->binary_operator_registry->getRegisteredByPrecedence() as $list){
			$assignment_type = $list->getAssignmentType();
			$operators = $list->getOperators();
			if($assignment_type === BinaryOperatorAssignmentType::LEFT){
				$index = -1;
				$count = count($tokens);
				while(++$index < $count){
					$value = $tokens[$index];
					if($value instanceof BinaryOperatorToken && isset($operators[$value->getOperator()])){
						array_splice($tokens, $index - 1, 3, [[
							$tokens[$index - 1],
							$value,
							$tokens[$index + 1]
						]]);
						$index = -1;
						$count = count($tokens);
					}
				}
			}elseif($assignment_type === BinaryOperatorAssignmentType::RIGHT){
				$index = count($tokens);
				while(--$index >= 0){
					$value = $tokens[$index];
					if($value instanceof BinaryOperatorToken && isset($operators[$value->getOperator()])){
						array_splice($tokens, $index - 1, 3, [[
							$tokens[$index - 1],
							$value,
							$tokens[$index + 1]
						]]);
						$index = count($tokens);
					}
				}
			}else{
				throw new RuntimeException("Invalid value supplied for binary operator assignment: {$assignment_type}");
			}
		}
	}

	/**
	 * Transforms a given token tree in-place by grouping all function calls with their
	 * parameters and resolving optional function parameters by their default values.
	 *
	 * @param string $expression
	 * @param Token[]|Token[][] $token_tree
	 */
	private function transformFunctionCallTokens(string $expression, array &$token_tree) : void{
		for($i = count($token_tree) - 1; $i >= 0; --$i){
			$token = $token_tree[$i];
			if(is_array($token)){
				$this->transformFunctionCallTokens($expression, $token_tree[$i]);
				continue;
			}

			if(!($token instanceof FunctionCallToken)){
				continue;
			}

			try{
				$function = $this->function_registry->get($token->getFunction());
			}catch(InvalidArgumentException $e){
				throw new ParseException("Cannot resolve function call at \"" . substr($expression, $token->getStartPos(), $token->getEndPos() - $token->getStartPos()) . "\" ({$token->getStartPos()}:{$token->getEndPos()}) in \"{$expression}\": {$e->getMessage()}");
			}

			$param_tokens = isset($token_tree[$i + 1]) ? (
				is_array($token_tree[$i + 1]) ? $token_tree[$i + 1] : [$token_tree[$i + 1]]
			) : [];

			if(isset($param_tokens[0]) && $param_tokens[0] instanceof FunctionCallArgumentSeparatorToken){
				array_unshift($param_tokens, null);
			}

			$last = array_key_last($param_tokens);
			if($last !== null && $param_tokens[$last] instanceof FunctionCallArgumentSeparatorToken){
				$param_tokens[] = null;
			}

			for($j = count($param_tokens) - 1; $j >= 1; --$j){
				if(
					$param_tokens[$j] instanceof FunctionCallArgumentSeparatorToken &&
					$param_tokens[$j - 1] instanceof FunctionCallArgumentSeparatorToken
				){
					array_splice($param_tokens, $j - 1, 2, [$param_tokens[$j - 1], null, $param_tokens[$j]]);
				}
			}

			$params = [];
			for($j = 0, $max = count($param_tokens); $j < $max; ++$j){
				$param_token = $param_tokens[$j];
				if(
					$param_token instanceof FunctionCallArgumentSeparatorToken &&
					$param_tokens[$j - 1] instanceof FunctionCallArgumentSeparatorToken
				){
					throw new ParseException("Unexpected {$param_token->getType()->getName()} token encountered at \"" . substr($expression, $param_token->getStartPos(), $param_token->getEndPos() - $param_token->getStartPos()) . "\" ({$param_token->getStartPos()}:{$param_token->getEndPos()}) in \"{$expression}\"");
				}
				if($j % 2 === 0){
					$params[] = $param_token;
				}
			}

			for($j = count($params), $max = count($function->fallback_param_values); $j < $max; ++$j){
				$params[] = null;
			}

			$l = 0;
			for($j = 0, $max = count($params); $j < $max; ++$j){
				if($params[$j] === null){
					if(isset($function->fallback_param_values[$j])){
						$params[$j] = new NumericLiteralToken($token->getStartPos() + $l, $token->getEndPos() + $l, $function->fallback_param_values[$j]);
						++$l;
					}else{
						throw new ParseException(
							"Cannot resolve function call at \"" . substr($expression, $token->getStartPos(), $token->getEndPos() - $token->getStartPos()) . "\" ({$token->getStartPos()}:{$token->getEndPos()}) in \"{$expression}\": " .
							"Function \"{$token->getFunction()}\" does not have a default value for parameter #" . ($j + 1)
						);
					}
				}
			}

			if(count($params) > count($function->fallback_param_values)){
				throw new ParseException(
					"Too many parameters supplied to function call at \"" . substr($expression, $token->getStartPos(), $token->getEndPos() - $token->getStartPos()) . "\" ({$token->getStartPos()}:{$token->getEndPos()}) in \"{$expression}\": " .
					"Expected " . count($function->fallback_param_values) . " parameter" . (count($function->fallback_param_values) === 1 ? "" : "s") . ", got " . count($params) . " parameter" . (count($params) === 1 ? "" : "s")
				);
			}

			array_splice($token_tree, $i, 2, [[$token, ...$params]]);
		}
	}

	/**
	 * Transforms a given token tree in-place to a flattened postfix representation.
	 *
	 * @param string $expression
	 * @param Token[]|Token[][] $postfix_token_tree
	 */
	public function convertTokenTreeToPostfixTokenTree(string $expression, array &$postfix_token_tree) : void{
		$stack = [&$postfix_token_tree];
		while(($index = array_key_last($stack)) !== null){
			$entry = &$stack[$index];
			unset($stack[$index]);

			if($entry[0] instanceof FunctionCallToken){
				$entry[] = array_shift($entry);
			}

			$count = count($entry);
			if($count === 3 && $entry[1] instanceof BinaryOperatorToken){
				$entry = [&$entry[0], &$entry[2], $entry[1]];
			}

			for($i = 0; $i < $count; ++$i){
				if(is_array($entry[$i])){
					$stack[] = &$entry[$i];
				}
			}
		}

		// flatten tree
		$stack = [&$postfix_token_tree];
		while(($index = array_key_last($stack)) !== null){
			$entry = &$stack[$index];
			unset($stack[$index]);

			$count = count($entry);
			for($i = 0; $i < $count; ++$i){
				if(is_array($entry[$i])){
					array_splice($entry, $i, 1, $entry[$i]);
					$stack[] = &$entry;
					break;
				}
			}
		}
	}
}