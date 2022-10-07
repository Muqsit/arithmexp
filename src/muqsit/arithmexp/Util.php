<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use Closure;
use Generator;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use function array_slice;
use function array_splice;
use function count;
use function is_array;

final class Util{

	/**
	 * @template T
	 * @param T[]|T[][] $array
	 * @return Generator<T[]>
	 */
	public static function &traverseNestedArray(array &$array) : Generator{
		$stack = [];

		$queue = [&$array];
		while(($index = array_key_first($queue)) !== null){
			$array = &$queue[$index];
			unset($queue[$index]);

			$stack[] = &$array;
			for($i = 0, $count = count($array); $i < $count; ++$i){
				if(is_array($array[$i])){
					$queue[] = &$array[$i];
				}
			}
		}

		$index = count($stack);
		while($index > 0){
			yield $stack[--$index];
		}
	}

	/**
	 * @param mixed[] $array
	 * @param (Closure(mixed[]) : bool)|null $filter
	 */
	public static function flattenArray(array &$array, ?Closure $filter = null) : void{
		$stack = [&$array];
		while(($index = array_key_last($stack)) !== null){
			$entry = &$stack[$index];
			unset($stack[$index]);

			$count = count($entry);
			for($i = 0; $i < $count; ++$i){
				if(is_array($entry[$i]) && ($filter === null || $filter($entry[$i]))){
					array_splice($entry, $i, 1, $entry[$i]);
					$stack[] = &$entry;
					break;
				}
			}
		}
	}

	/**
	 * @param ExpressionToken[] $postfix_expression_tokens
	 * @param int $offset
	 * @param int|null $length
	 * @return ExpressionToken[]|ExpressionToken[][]|ExpressionToken[][][]|ExpressionToken[][][][]
	 */
	public static function expressionTokenArrayToTree(array $postfix_expression_tokens, int $offset = 0, ?int $length = null) : array{
		$length ??= count($postfix_expression_tokens);
		$tree = [];
		for($i = $offset; $i < $length; ++$i){
			$operand = $postfix_expression_tokens[$i];
			$tree[] = $operand;
			if($operand instanceof FunctionCallExpressionToken){
				$replace = $operand->argument_count + 1;
				$args = array_slice($tree, -$replace, $replace);
				array_splice($tree, -$replace, $replace, count($args) === 1 ? $args : [$args]);
			}
		}
		return $tree;
	}
}