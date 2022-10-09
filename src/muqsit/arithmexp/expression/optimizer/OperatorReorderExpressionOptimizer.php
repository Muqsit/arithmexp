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
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\Util;
use function array_filter;
use function count;
use function is_array;
use function usort;

final class OperatorReorderExpressionOptimizer implements ExpressionOptimizer{

	public function __construct(){
	}

	public function run(Parser $parser, Expression $expression) : Expression{
		$binary_operator_registry = $parser->getBinaryOperatorRegistry();
		$postfix_expression_tokens = $expression->getPostfixExpressionTokens();
		$tree = Util::expressionTokenArrayToTree($postfix_expression_tokens);
		$changed = false;
		foreach(Util::traverseNestedArray($tree) as &$entry){
			if(count($entry) !== 3){
				continue;
			}

			$token = $entry[2];
			if(
				!($token instanceof FunctionCallExpressionToken) ||
				!$token->commutative
			){
				continue;
			}

			$entries = [$entry[0], $entry[1], $entry[2]]; // copy &array
			Util::flattenArray($entries, static fn(array $e) : bool => (count($e) === 1 && is_array($e[0])) || (
				count($e) === 3 &&
				$e[2] instanceof FunctionCallExpressionToken &&
				$e[2]->commutative &&
				$e[2]->function === $token->function
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

		if($a instanceof NumericLiteralExpressionToken && $b instanceof NumericLiteralExpressionToken){
			return $a->value <=> $b->value;
		}
		if($a instanceof NumericLiteralExpressionToken){
			return -1;
		}
		if($b instanceof NumericLiteralExpressionToken){
			return 1;
		}

		if($a instanceof VariableExpressionToken && $b instanceof VariableExpressionToken){
			return $a->label <=> $b->label;
		}
		return 0;
	}
}