<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\ConstantExpression;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use function array_filter;
use function array_map;
use function array_slice;
use function array_splice;
use function count;

final class ConstantFoldingExpressionOptimizer implements ExpressionOptimizer{

	public function __construct(){
	}

	public function run(Expression $expression) : Expression{
		$postfix_expression_tokens = $expression->getPostfixExpressionTokens();
		do{
			$found = false;
			foreach($postfix_expression_tokens as $i => $token){
				if(!($token instanceof FunctionCallExpressionToken) || !$token->isDeterministic()){
					continue;
				}

				$params = array_slice($postfix_expression_tokens, $i - $token->argument_count, $token->argument_count);
				if(count(array_filter($params, static fn(ExpressionToken $token) : bool => $token instanceof FunctionCallExpressionToken || !$token->isDeterministic())) > 0){
					continue;
				}

				array_splice($postfix_expression_tokens, $i - $token->argument_count, $token->argument_count + 1, [new NumericLiteralExpressionToken(($token->function)(...array_map(fn(ExpressionToken $token) : int|float => $token->getValue($expression, []), $params)))]);
				$found = true;
				break;
			}
		}while($found);

		return count($postfix_expression_tokens) === 1 && $postfix_expression_tokens[0]->isDeterministic() ?
			new ConstantExpression($expression->getExpression(), $postfix_expression_tokens, $postfix_expression_tokens[0]->getValue($expression, [])) :
			new RawExpression($expression->getExpression(), $postfix_expression_tokens);
	}
}