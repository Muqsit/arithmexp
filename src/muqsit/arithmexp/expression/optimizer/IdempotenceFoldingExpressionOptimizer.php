<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\function\FunctionFlags;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\Util;
use function array_splice;
use function count;

final class IdempotenceFoldingExpressionOptimizer implements ExpressionOptimizer{

	public function __construct(){
	}

	public function run(Parser $parser, Expression $expression) : Expression{
		$postfix_expression_token_tree = Util::expressionTokenArrayToTree($expression->getPostfixExpressionTokens());
		$changes = 0;
		do{
			$found = false;
			foreach(Util::traverseNestedArray($postfix_expression_token_tree) as &$entry){
				for($i = count($entry) - 1; $i >= 0; --$i){
					$token = $entry[$i];
					if(!($token instanceof FunctionCallExpressionToken) || $token->argument_count !== 1 || ($token->flags & FunctionFlags::IDEMPOTENT) === 0){
						continue;
					}

					$arg = [$entry[$i - $token->argument_count]];
					Util::flattenArray($arg);
					$end_token = $arg[count($arg) - 1];
					if(!($end_token instanceof FunctionCallExpressionToken) || !$end_token->equals($token)){
						continue;
					}

					array_splice($entry, $i - $token->argument_count, 1 + $token->argument_count, $arg);
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