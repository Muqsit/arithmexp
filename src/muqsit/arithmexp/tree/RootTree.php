<?php

declare(strict_types=1);

namespace muqsit\arithmexp\tree;

use muqsit\arithmexp\ArithmeticExpression;
use muqsit\arithmexp\operator\Operator;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\token\TokenType;
use muqsit\arithmexp\token\TokenUtil;
use muqsit\arithmexp\util\ArithmeticExpressionException;
use RuntimeException;
use SplStack;

final class RootTree implements Tree{

	private Tree $tree;

	public function __construct(ArithmeticExpression $arithmetic_expression){
		$operator_registry = $arithmetic_expression->getOperatorRegistry();
		$expression = $arithmetic_expression->getExpression();
		$tokens = $arithmetic_expression->getTokens();

		$filtered_tokens = [];
		foreach($tokens as $token){
			if($token->type === TokenType::INVALID){
				throw new ArithmeticExpressionException("Invalid token '" . TokenUtil::stringifyType($token->type) . "' encountered while parsing '{$expression}' at " . TokenUtil::stringifyValueAndPosition($token));
			}
			if($token->type === TokenType::WHITESPACE){
				continue;
			}
			$filtered_tokens[] = $token;
		}

		$simplified = $filtered_tokens;
		do{
			$brackets = new SplStack();
			$tokens_c = count($simplified);
			$bracket_pair = null;
			for($index = 0; $index < $tokens_c; ++$index){
				$token = $simplified[$index];
				if(!($token instanceof Token)){
					continue;
				}

				if($token->type === TokenType::BRACKET_OPEN){
					$brackets->push($index);
				}elseif($token->type === TokenType::BRACKET_CLOSE){
					try{
						$open_pair_index = $brackets->pop();
					}catch(RuntimeException $e){
						throw new ArithmeticExpressionException("Could not match '" . TokenUtil::stringifyType($token->type) . "' to a '" . TokenUtil::stringifyType(TokenType::BRACKET_OPEN) . "' while parsing '{$expression}' at " . TokenUtil::stringifyValueAndPosition($token), $e->getCode(), $e);
					}

					$bracket_pair = [$open_pair_index, $index];
					break;
				}
			}

			if($bracket_pair === null && count($simplified) > 1){
				$bracket_pair = [-1, count($simplified)];
			}

			$has_group = false;
			if($bracket_pair !== null){
				$has_group = true;

				[$open_pair_index, $close_pair_index] = $bracket_pair;
				$entries = [];
				for($i = $open_pair_index + 1; $i < $close_pair_index; ++$i){
					$entries[] = $simplified[$i];
					unset($simplified[$i]);
				}

				foreach($operator_registry->getAllByPriority() as $operators){
					do{
						$changed = false;
						foreach($entries as $index => $entry){
							if($entry instanceof Token && $entry->type === TokenType::OPERATOR && isset($operators[$entry->text])){
								try{
									$tree = $this->parseOperands($entry, $operators[$entry->text], $entries[$index - 1] ?? null, $entries[$index + 1] ?? null);
								}catch(ArithmeticExpressionException $e){
									throw new ArithmeticExpressionException("{$e->getMessage()} while parsing '{$expression}'", $e->getCode(), $e);
								}
								$entries[$index - 1] = $tree;
								unset($entries[$index + 1], $entries[$index]);
								$entries = array_values($entries);
								$changed = true;
								break;
							}
						}
					}while($changed);
				}

				if(count($entries) > 1){
					throw new ArithmeticExpressionException("Failed to reduce sub-expression while parsing '{$expression}'");
				}

				unset($simplified[$open_pair_index]);
				$simplified[$close_pair_index] = current($entries);
				$simplified = array_values($simplified);
			}
		}while($has_group);

		if(count($simplified) > 1){
			throw new ArithmeticExpressionException("Failed to reduce sub-expression while parsing '{$expression}'");
		}

		$this->tree = current($simplified);
	}

	private function parseOperands(Token $operator_token, Operator $operator, Tree|Token|null $left, Tree|Token|null $right) : Tree{
		if($left instanceof Token){
			$left = match($left->type){
				TokenType::NUMBER => new NumericConstantTree((float) $left->text),
				TokenType::SYMBOL => new NumericVariableTree($left->text),
				default => throw new ArithmeticExpressionException("No left-side operand found for '" . TokenUtil::stringifyType($operator_token->type) . "' at " . TokenUtil::stringifyValueAndPosition($operator_token))
			};
		}elseif($left === null){
			$default = $operator->getDefaultLeftValue() ?? throw new ArithmeticExpressionException("No left-side operand found for '" . TokenUtil::stringifyType($operator_token->type) . "' at " . TokenUtil::stringifyValueAndPosition($operator_token));
			$left = new NumericConstantTree($default);
		}

		if($right instanceof Token){
			$right = match($right->type){
				TokenType::NUMBER => new NumericConstantTree((float) $right->text),
				TokenType::SYMBOL => new NumericVariableTree($right->text),
				default => throw new ArithmeticExpressionException("No right-side operand found for '" . TokenUtil::stringifyType($operator_token->type) . "' at " . TokenUtil::stringifyValueAndPosition($operator_token))
			};
		}elseif($right === null){
			$default = $operator->getDefaultLeftValue() ?? throw new ArithmeticExpressionException("No right-side operand found for '" . TokenUtil::stringifyType($operator_token->type) . "' at " . TokenUtil::stringifyValueAndPosition($operator_token));
			$right = new NumericConstantTree($default);
		}

		return new BinaryOperationTree($operator, $left, $right);
	}

	/**
	 * @param array<string, float> $variables
	 * @return float
	 */
	public function getValue(array $variables) : float{
		return $this->tree->getValue($variables);
	}
}