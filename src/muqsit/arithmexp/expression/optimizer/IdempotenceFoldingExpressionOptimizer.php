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
				if(!is_array($entry) || !isset($entry[$index = count($entry) - 1]) || !($entry[$index = count($entry) - 1] instanceof FunctionCallExpressionToken)){
					continue;
				}

				$token = $entry[$index];
				if(($token->flags & FunctionFlags::IDEMPOTENT) === 0){
					continue;
				}

				if($token->argument_count === 0){
					continue;
				}

				$is_commutative = ($token->flags & FunctionFlags::COMMUTATIVE) > 0;
				if(!$is_commutative){
					if($token->argument_count !== 1){
						continue;
					}

					$args = $entry[$index - 1];
					if(
						!is_array($args) ||
						!($args[count($args) - 1] instanceof FunctionCallExpressionToken) ||
						!$args[count($args) - 1]->equals($token)
					){
						continue;
					}

					$entry = $args;
					$found = true;
					++$changes;
				}else{
					for($i = $token->argument_count - 1; $i >= 0; --$i){
						$args = $entry[$i];
						if(
							!is_array($args) ||
							!($args[$j = count($args) - 1] instanceof FunctionCallExpressionToken) ||

							// TODO: Have ExpressionToken::equals() return inequality report instead, so inequality between specific attributes can be filtered
							// below, the attribute "argument_count" is being filtered
							$args[$j]->name !== $token->name ||
							$args[$j]->function !== $token->function ||
							$args[$j]->flags !== $token->flags
						){
							continue;
						}

						array_splice($entry, $i, 1, array_slice($args, 0, -1));
						$i += count($args) - 1;
						$found = true;
						++$changes;
					}
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