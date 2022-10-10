<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\Util;
use function array_filter;
use function array_key_last;
use function count;
use function is_array;
use function usort;

final class ReorderExpressionOptimizer implements ExpressionOptimizer{

	public function __construct(){
	}

	public function run(Parser $parser, Expression $expression) : Expression{
		$postfix_expression_tokens = $expression->getPostfixExpressionTokens();
		$tree = Util::expressionTokenArrayToTree($postfix_expression_tokens);
		$changed = false;
		foreach(Util::traverseNestedArray($tree) as &$entry){
			$index = array_key_last($entry);
			if($index === null){
				continue;
			}

			$token = $entry[$index];
			if(
				!($token instanceof FunctionCallExpressionToken) ||
				!$token->commutative
			){
				continue;
			}

			// copy &array
			$entries = [];
			foreach($entry as $value){
				$entries[] = $value;
			}

			Util::flattenArray($entries, static fn(array $e) : bool => (count($e) === 1 && is_array($e[0])) || (
				($index = array_key_last($e)) !== null &&
				$e[$index] instanceof FunctionCallExpressionToken &&
				$e[$index]->commutative &&
				$e[$index]->function === $token->function
			));
			$operands = array_filter($entries, static fn(ExpressionToken|array $token) : bool => !($token instanceof FunctionCallExpressionToken));
			usort($operands, fn(ExpressionToken|array $a, ExpressionToken|array $b) : int => match(true){
				is_array($a) => is_array($b) ? 0 : 1,
				is_array($b) => -1,
				default => $this->compare($a, $b)
			});

			$j = 0;
			foreach($entries as $i => $value){
				if(!($value instanceof FunctionCallExpressionToken)){
					$index = $j++;
					if($value !== $operands[$index]){
						$changed = true;
						$entries[$i] = $operands[$index];
					}
				}
			}
			$entry = Util::expressionTokenArrayToTree($entries);
		}

		unset($entry);
		if(!$changed){
			return $expression;
		}

		Util::flattenArray($tree);
		/** @var ExpressionToken[] $tree */

		return new RawExpression($expression->getExpression(), $tree);
	}

	/**
	 * @param ExpressionToken $a
	 * @param ExpressionToken $b
	 * @return int<-1, 1>
	 */
	private function compare(ExpressionToken $a, ExpressionToken $b) : int{
		if($a instanceof FunctionCallExpressionToken && $b instanceof FunctionCallExpressionToken){
			return $a->name <=> $b->name;
		}
		if($a instanceof FunctionCallExpressionToken){
			return 1;
		}
		if($b instanceof FunctionCallExpressionToken){
			return -1;
		}

		if($a instanceof VariableExpressionToken && $b instanceof VariableExpressionToken){
			return $a->label <=> $b->label;
		}
		if($a instanceof VariableExpressionToken){
			return -1;
		}
		if($b instanceof VariableExpressionToken){
			return 1;
		}

		if($a instanceof NumericLiteralExpressionToken && $b instanceof NumericLiteralExpressionToken){
			return $a->value <=> $b->value;
		}
		return 0;
	}
}