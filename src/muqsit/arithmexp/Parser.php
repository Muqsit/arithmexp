<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use muqsit\arithmexp\expression\BinaryOperatorRegistry;
use muqsit\arithmexp\expression\ConstantRegistry;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\expression\token\OperatorExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\LeftParenthesisToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\RightParenthesisToken;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\token\UnaryOperatorToken;
use muqsit\arithmexp\token\VariableToken;
use RuntimeException;
use function array_key_last;
use function array_map;
use function array_splice;
use function count;
use function is_array;
use function substr;

final class Parser{

	public static function createDefault() : self{
		return new self(
			BinaryOperatorRegistry::createDefault(),
			ConstantRegistry::createDefault(),
			Scanner::createDefault()
		);
	}

	public function __construct(
		private BinaryOperatorRegistry $binary_operator_registry,
		private ConstantRegistry $constant_registry,
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
		$this->transformUnaryOperatorTokens($tokens);
		$this->groupBinaryOperations($expression, $tokens);
		$this->convertTokenTreeToPostfixTokenTree($tokens);
		return new Expression(
			$this->binary_operator_registry,
			$expression,
			array_map(function(Token $token) : ExpressionToken{
				if($token instanceof BinaryOperatorToken){
					return new OperatorExpressionToken($token->getOperator());
				}
				if($token instanceof NumericLiteralToken){
					return new NumericLiteralExpressionToken($token->getValue());
				}
				if($token instanceof VariableToken){
					$label = $token->getLabel();
					$constant_value = $this->constant_registry->registered[$label] ?? null;
					return $constant_value !== null ? new NumericLiteralExpressionToken($constant_value) : new VariableExpressionToken($label);
				}
				throw new RuntimeException("Don't know how to convert {$token->getType()->getName()} token to " . ExpressionToken::class);
			}, $tokens)
		);
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

			array_splice($tokens, $i, 1 + ($j - $i), count($group) === 1 ? $group : [$group]);
			$i = count($tokens) - 1;
		}
	}

	/**
	 * Transforms a given token tree in-place by replacing {@see UnaryOperatorToken}
	 * instances together with the operand with [NumericLiteralToken(val: +1 or -1),
	 * BinaryOperatorToken(op: *), <Token> (Operand)].
	 *
	 * @param Token[]|Token[][] $tokens
	 */
	private function transformUnaryOperatorTokens(array &$tokens) : void{
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

				array_splice($entry, $i, 2, [
					new NumericLiteralToken($token->getStartPos(), $token->getEndPos(), $token->getFactor()),
					new BinaryOperatorToken($token->getStartPos(), $token->getEndPos(), BinaryOperatorToken::OPERATOR_TYPE_MULTIPLICATION),
					$entry[$i + 1]
				]);
			}
		}
	}

	/**
	 * Transforms a given token tree in-place by grouping all binary operations for
	 * low-complexity processing, converting [TOK, BOP, TOK, BOP, TOK] to
	 * [[[TOK, BOP, TOK], BOP, TOK]].
	 *
	 * @param string $expression
	 * @param Token[]|Token[][] $tokens
	 */
	private function groupBinaryOperations(string $expression, array &$tokens) : void{
		$stack = [&$tokens];
		while(($index = array_key_last($stack)) !== null){
			$entry = &$stack[$index];
			unset($stack[$index]);

			foreach($entry as $i => $value){
				if(is_array($value) && count($value) !== 3){
					$stack[] = &$entry;
					$stack[] = &$entry[$i];
					continue 2;
				}
			}

			foreach(BinaryOperatorToken::OPERATOR_PRECEDENCE as $operator){
				$index = count($entry);
				while(--$index >= 0){
					$value = $entry[$index];
					if($value instanceof BinaryOperatorToken && $value->getOperator() === $operator){
						array_splice($entry, $index - 1, 3, [[
							$entry[$index - 1],
							$value,
							$entry[$index + 1]
						]]);
						$index = count($entry);
					}
				}
				if(count($entry) === 1){
					$entry = $entry[0];
					break;
				}
			}
		}

		/** @var Token|Token[]|Token[][] $tokens */
		if($tokens instanceof Token){
			$tokens = [$tokens];
		}elseif(count($tokens) !== 3 || !($tokens[1] instanceof BinaryOperatorToken)){
			/** @var Token $invalid */
			$invalid = $tokens[1];
			throw new ParseException("Unexpected {$invalid->getType()->getName()} token encountered at \"" . substr($expression, $invalid->getStartPos(), $invalid->getEndPos() - $invalid->getStartPos()) . "\" ({$invalid->getStartPos()}:{$invalid->getEndPos()}) in \"{$expression}\"");
		}
	}

	/**
	 * Transforms a given token tree in-place to a flattened postfix representation.
	 *
	 * @param Token[]|Token[][] $postfix_token_tree
	 */
	public function convertTokenTreeToPostfixTokenTree(array &$postfix_token_tree) : void{
		$stack = [&$postfix_token_tree];
		while(($index = array_key_last($stack)) !== null){
			$entry = &$stack[$index];
			unset($stack[$index]);

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