<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use Closure;
use Generator;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\OpcodeExpressionToken;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\token\UnaryOperatorToken;
use function array_map;
use function array_slice;
use function array_splice;
use function count;
use function is_array;

final class Util{

	/**
	 * @template T
	 * @param list<T|list<T>> $array
	 * @return Generator<list<T>>
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
	 * @template T
	 * @param list<T|list<T>> $array
	 * @param-out list<T> $array
	 * @param (Closure(list<T>) : bool)|null $filter
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

	public static function asFunctionCallExpressionToken(Parser $parser, ExpressionToken $token) : ?FunctionCallExpressionToken{
		if($token instanceof FunctionCallExpressionToken){
			return $token;
		}
		if($token instanceof OpcodeExpressionToken){
			$previous = $token->parent;
			if($previous instanceof BinaryOperatorToken){
				$function_info = $parser->operator_manager->getBinaryRegistry()->get($previous->operator)->getFunction();
				return new FunctionCallExpressionToken($token->getPos(), $previous->operator, 2, $function_info->getClosure(), $function_info->getFlags(), $token->parent);
			}
			if($previous instanceof UnaryOperatorToken){
				$function_info = $parser->operator_manager->getUnaryRegistry()->get($previous->operator)->getFunction();
				return new FunctionCallExpressionToken($token->getPos(), $previous->operator, 1, $function_info->getClosure(), $function_info->getFlags(), $token->parent);
			}
		}
		return null;
	}

	/**
	 * @param Parser $parser
	 * @param list<ExpressionToken> $postfix_expression_tokens
	 * @param int $offset
	 * @param int|null $length
	 * @return list<ExpressionToken|list<ExpressionToken>>
	 */
	public static function expressionTokenArrayToTree(Parser $parser, array $postfix_expression_tokens, int $offset = 0, ?int $length = null) : array{
		$length ??= count($postfix_expression_tokens);
		/** @var list<ExpressionToken|list<ExpressionToken>> $tree */
		$tree = [];
		for($i = $offset; $i < $length; ++$i){
			$operand = $postfix_expression_tokens[$i];
			$tree[] = $operand;
			$token = self::asFunctionCallExpressionToken($parser, $operand);
			if($token !== null){
				$replace = $token->argument_count + 1;
				$args = array_slice($tree, -$replace, $replace);
				array_splice($tree, -$replace, $replace, count($args) === 1 ? $args : [$args]);
			}
		}
		return $tree;
	}

	/**
	 * @param list<Token> $tokens
	 * @return Position
	 */
	public static function positionContainingTokens(array $tokens) : Position{
		return Position::containing(array_map(static fn(Token $token) : Position => $token->getPos(), $tokens));
	}

	/**
	 * @param list<ExpressionToken> $tokens
	 * @return Position
	 */
	public static function positionContainingExpressionTokens(array $tokens) : Position{
		return Position::containing(array_map(static fn(ExpressionToken $token) : Position => $token->getPos(), $tokens));
	}
}