<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\function\FunctionFlags;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\Util;
use function array_splice;
use function count;
use function is_array;

final class IdempotenceFoldingExpressionOptimizer implements ExpressionOptimizer{

	public function __construct(){
	}

	public function run(Parser $parser, Expression $expression) : Expression{
		$postfix_expression_token_tree = Util::expressionTokenArrayToTree($parser, $expression->getPostfixExpressionTokens());
		$changes = 0;
		do{
			/** @var list<ExpressionToken|list<ExpressionToken>> $postfix_expression_token_tree */
			$found = false;
			foreach(Util::traverseNestedArray($postfix_expression_token_tree) as &$entry){
				if(!is_array($entry) || !isset($entry[$index = count($entry) - 1]) || !($entry[$index] instanceof ExpressionToken)){
					continue;
				}

				$token = Util::asFunctionCallExpressionToken($parser, $entry[$index]);
				if($token === null){
					continue;
				}
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
					if(!is_array($args)){
						continue;
					}

					$other_token = Util::asFunctionCallExpressionToken($parser, $args[count($args) - 1]);
					if($other_token === null || !$other_token->equals($token)){
						continue;
					}

					$entry = $args;
					$found = true;
					++$changes;
				}else{
					for($i = $token->argument_count - 1; $i >= 0; --$i){
						$args = $entry[$i];
						if(!is_array($args)){
							continue;
						}

						$other_token = Util::asFunctionCallExpressionToken($parser, $args[count($args) - 1]);
						if($other_token === null){
							continue;
						}

						if(
							// TODO: Have ExpressionToken::equals() return inequality report instead, so inequality between specific attributes can be filtered
							// below, the attribute "argument_count" is being filtered
							$other_token->name !== $token->name ||
							$other_token->function !== $token->function ||
							$other_token->flags !== $token->flags
						){
							continue;
						}

						array_splice($entry, $i, 1, array_slice($args, 0, -1));
						$i += count($args) - 1;
						$found = true;
						++$changes;
					}

					$token->argument_count = count($entry) - 1;
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