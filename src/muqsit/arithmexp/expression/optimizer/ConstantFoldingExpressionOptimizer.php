<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\ConstantExpression;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\Util;
use function array_filter;
use function array_map;
use function array_slice;
use function array_splice;
use function count;

final class ConstantFoldingExpressionOptimizer implements ExpressionOptimizer{

	public function __construct(){
	}

	public function run(Parser $parser, Expression $expression) : Expression{
		$postfix_expression_tokens = $expression->getPostfixExpressionTokens();
		$changes = 0;
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

				array_splice($postfix_expression_tokens, $i - $token->argument_count, $token->argument_count + 1, [
					new NumericLiteralExpressionToken(
						Util::positionContainingExpressionTokens($params),
						($token->function)(...array_map(static fn(ExpressionToken $token) : int|float => $token->retrieveValue($expression, []), $params))
					)
				]);
				$found = true;
				++$changes;
				break;
			}
		}while($found);

		if($changes === 0){
			return $expression;
		}

		return count($postfix_expression_tokens) === 1 && $postfix_expression_tokens[0]->isDeterministic() ?
			new ConstantExpression($expression->getExpression(), $postfix_expression_tokens[0]->retrieveValue($expression, [])) :
			new RawExpression($expression->getExpression(), $postfix_expression_tokens);
	}
}