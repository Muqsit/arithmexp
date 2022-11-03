<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use DivisionByZeroError;
use muqsit\arithmexp\expression\ConstantExpression;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\Util;
use RuntimeException;
use function array_filter;
use function array_map;
use function array_slice;
use function array_splice;
use function count;

final class ConstantFoldingExpressionOptimizer implements ExpressionOptimizer{

	/**
	 * @param Expression $expression
	 * @param FunctionCallExpressionToken $function
	 * @param Token[] $arguments
	 * @return int|float|null
	 * @throws ParseException
	 */
	public static function evaluateFunctionCallTokens(Expression $expression, FunctionCallExpressionToken $function, array $arguments) : int|float|null{
		if(count(array_filter($arguments, static fn(ExpressionToken $token) : bool => $token instanceof FunctionCallExpressionToken || !$token->isDeterministic())) > 0){
			return null;
		}

		$args = array_map(static fn(ExpressionToken $token) : int|float => $token->retrieveValue($expression, []), $arguments);
		try{
			$value = ($function->function)(...$args);
		}catch(DivisionByZeroError $e){
			if($function->parent instanceof BinaryOperatorToken){
				$rvalue_flattened = [$arguments[1]];
				Util::flattenArray($rvalue_flattened);
				$pos = Util::positionContainingExpressionTokens($rvalue_flattened);
				match($function->parent->getOperator()){
					"/" => throw ParseException::unresolvableExpressionDivisionByZero($expression->getExpression(), $pos),
					"%" => throw ParseException::unresolvableExpressionModuloByZero($expression->getExpression(), $pos),
					default => null
				};
			}
			throw new RuntimeException("Unexpected error thrown by function \"{$function->name}\"", $e->getCode(), $e);
		}
		return $value;
	}

	public function __construct(){
	}

	public function run(Parser $parser, Expression $expression) : Expression{
		$postfix_expression_tokens = $expression->getPostfixExpressionTokens();
		if(count($postfix_expression_tokens) === 1 && $postfix_expression_tokens[0]->isDeterministic()){
			return $expression instanceof ConstantExpression ? $expression : new ConstantExpression($expression->getExpression(), $postfix_expression_tokens[0]->retrieveValue($expression, []));
		}

		$postfix_expression_token_tree = Util::expressionTokenArrayToTree($postfix_expression_tokens);
		$changes = 0;
		do{
			$found = false;
			foreach(Util::traverseNestedArray($postfix_expression_token_tree) as &$entry){
				for($i = count($entry) - 1; $i >= 0; --$i){
					$token = $entry[$i];
					if(!($token instanceof FunctionCallExpressionToken) || !$token->isDeterministic()){
						continue;
					}

					$arg_tokens = array_slice($entry, $i - $token->argument_count, $token->argument_count);
					Util::flattenArray($arg_tokens);
					$value = self::evaluateFunctionCallTokens($expression, $token, $arg_tokens);
					if($value === null){
						continue;
					}

					array_splice($entry, $i - $token->argument_count, 1 + $token->argument_count, [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$arg_tokens, $token]), $value)]);
					$i -= $token->argument_count;
					$found = true;
					++$changes;
				}
			}
			unset($entry);
		}while($found);

		if($changes === 0){
			return $expression;
		}

		Util::flattenArray($postfix_expression_token_tree);
		return new RawExpression($expression->getExpression(), $postfix_expression_token_tree);
	}
}