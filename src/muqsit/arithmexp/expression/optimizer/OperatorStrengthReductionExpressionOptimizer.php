<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\ConstantExpression;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\UnaryOperatorToken;
use muqsit\arithmexp\Util;
use RuntimeException;
use function array_splice;
use function assert;
use function count;
use function gettype;
use function is_array;

final class OperatorStrengthReductionExpressionOptimizer implements ExpressionOptimizer{

	public function __construct(){
	}

	public function run(Parser $parser, Expression $expression) : Expression{
		$postfix_expression_tokens = $expression->getPostfixExpressionTokens();

		$changes = 0;
		for($i = count($postfix_expression_tokens) - 1; $i >= 1; --$i){
			$token = $postfix_expression_tokens[$i];
			if(!($token instanceof FunctionCallExpressionToken)){
				continue;
			}

			$tree = Util::expressionTokenArrayToTree($postfix_expression_tokens, 0, $i);
			$tree_c = count($tree);

			$param_tokens = [];
			$param_tokens_c = 0;
			for($j = 0; $j < $token->argument_count; ++$j){
				$entry = $tree[($tree_c - $token->argument_count) + $j];
				if(is_array($entry)){
					Util::flattenArray($entry);
				}else{
					$entry = [$entry];
				}
				$param_tokens[] = $entry;
				$param_tokens_c += count($entry);
			}

			$replacement = match(true){
				$token->parent instanceof BinaryOperatorToken => $this->processBinaryExpression($parser, $expression, $token, $param_tokens),
				$token->parent instanceof UnaryOperatorToken => $this->processUnaryExpression($parser, $token, $param_tokens),
				default => null
			};
			if($replacement === null){
				continue;
			}

			array_splice($postfix_expression_tokens, $i - $param_tokens_c, $param_tokens_c + 1, $replacement);
			++$changes;
			$i = count($postfix_expression_tokens);
		}

		return match(true){
			count($postfix_expression_tokens) === 1 && $postfix_expression_tokens[0] instanceof NumericLiteralExpressionToken => new ConstantExpression($expression->getExpression(), $postfix_expression_tokens[0]->value),
			default => $changes > 0 ? new RawExpression($expression->getExpression(), $postfix_expression_tokens) : $expression
		};
	}

	/**
	 * @param ExpressionToken[] $tokens
	 * @param int $value
	 * @return bool
	 */
	private function valueEquals(array $tokens, int $value) : bool{
		return count($tokens) === 1 && $tokens[0] instanceof NumericLiteralExpressionToken && match(gettype($tokens[0]->value)){
			"integer" => $value === $tokens[0]->value,
			"double" => (float) $value === $tokens[0]->value,
			default => throw new RuntimeException("Unexpected numeric literal type: " . gettype($tokens[0]->value))
		};
	}

	/**
	 * @param ExpressionToken[] $x
	 * @param ExpressionToken[] $y
	 * @return bool
	 */
	private function tokensEqualByReturnValue(array $x, array $y) : bool{
		if(count($x) !== count($y)){
			return false;
		}

		for($i = 0, $max = count($x); $i < $max; ++$i){
			if((
				!($x[$i] instanceof VariableExpressionToken /* variables are deterministic during evaluation */) &&
				!$x[$i]->isDeterministic()
			) || !$x[$i]->equals($y[$i])){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Parser $parser
	 * @param FunctionCallExpressionToken $operator_token
	 * @param ExpressionToken[][] $param_tokens
	 * @return ExpressionToken[]|null
	 */
	private function processUnaryExpression(Parser $parser, FunctionCallExpressionToken $operator_token, array $param_tokens) : ?array{
		$token = $operator_token->parent;
		assert($token instanceof UnaryOperatorToken);

		$m_op = $parser->getBinaryOperatorRegistry()->get("*");

		[$operand] = $param_tokens;
		return match($token->getOperator()){
			"+" => $operand,
			"-" => [
				new NumericLiteralExpressionToken($token->getPos(), -1),
				...$operand,
				new FunctionCallExpressionToken(Util::positionContainingExpressionTokens([...$operand, $operator_token]), $m_op->getSymbol(), 2, $m_op->getOperator(), $m_op->isDeterministic(), $m_op->isCommutative(), new BinaryOperatorToken($token->getPos(), $m_op->getSymbol()))
			],
			default => null
		};
	}

	/**
	 * @param Parser $parser
	 * @param Expression $expression
	 * @param FunctionCallExpressionToken $operator_token
	 * @param ExpressionToken[][] $param_tokens
	 * @return ExpressionToken[]|null
	 * @throws ParseException
	 */
	private function processBinaryExpression(Parser $parser, Expression $expression, FunctionCallExpressionToken $operator_token, array $param_tokens) : ?array{
		$token = $operator_token->parent;
		assert($token instanceof BinaryOperatorToken);

		$m_op = $parser->getBinaryOperatorRegistry()->get("*");

		[$left, $right] = $param_tokens;
		return match($token->getOperator()){
			"**" => match(true){
				$this->valueEquals($left, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueEquals($left, 1) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 1)],
				$this->valueEquals($right, 2) && !$this->valueEquals($left, 2) => [
					...$left,
					...$left,
					new FunctionCallExpressionToken(Util::positionContainingExpressionTokens($right), $m_op->getSymbol(), 2, $m_op->getOperator(), $m_op->isDeterministic(), $m_op->isCommutative(), $token)
				],
				$this->valueEquals($right, 1) => $left,
				$this->valueEquals($right, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 1)],
				default => null
			},
			"*" => match(true){
				$this->valueEquals($left, 1) => $right,
				$this->valueEquals($right, 1) => $left,
				$this->valueEquals($left, 0), $this->valueEquals($right, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				default => null
			},
			"/" => match(true){
				$this->valueEquals($left, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueEquals($right, 0) => throw ParseException::unresolvableExpressionDivisionByZero($expression->getExpression(), Util::positionContainingExpressionTokens($right)),
				$this->valueEquals($right, 1) => $left,
				default => $this->processDivision($parser, $operator_token, $left, $right)
			},
			"+", => match(true){
				$this->valueEquals($left, 0) => $right,
				$this->valueEquals($right, 0) => $left,
				default => null
			},
			"-" => match(true){
				$this->tokensEqualByReturnValue($left, $right) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueEquals($left, 0) => $right,
				$this->valueEquals($right, 0) => $left,
				default => null
			},
			default => null
		};
	}

	/**
	 * @param Parser $parser
	 * @param FunctionCallExpressionToken $operator_token
	 * @param ExpressionToken[] $left
	 * @param ExpressionToken[] $right
	 * @return ExpressionToken[]|null
	 */
	private function processDivision(Parser $parser, FunctionCallExpressionToken $operator_token, array $left, array $right) : ?array{
		$binary_operator_registry = $parser->getBinaryOperatorRegistry();
		$m_op = $binary_operator_registry->get("*");
		$filter = static fn(array $array) : bool => count($array) === 1 || (
			count($array) === 3 &&
			$array[2] instanceof FunctionCallExpressionToken &&
			$array[2]->parent instanceof BinaryOperatorToken &&
			$binary_operator_registry->get($array[2]->parent->getOperator()) === $m_op
		);

		$left_tree = Util::expressionTokenArrayToTree($left);
		Util::flattenArray($left_tree, $filter);

		$right_tree = Util::expressionTokenArrayToTree($right);
		Util::flattenArray($right_tree, $filter);

		$changes = 0;
		do{
			$changed = false;
			foreach($left_tree as $i => $left_operand){
				if($left_operand instanceof FunctionCallExpressionToken || ($left_operand instanceof NumericLiteralExpressionToken && $left_operand->value === 1)){
					continue;
				}

				$left_operand = is_array($left_operand) ? $left_operand : [$left_operand];
				Util::flattenArray($left_operand);
				foreach($right_tree as $j => $right_operand){
					if($right_operand instanceof FunctionCallExpressionToken || ($right_operand instanceof NumericLiteralExpressionToken && $right_operand->value === 1)){
						continue;
					}

					$right_operand = is_array($right_operand) ? $right_operand : [$right_operand];
					Util::flattenArray($right_operand);
					$replacement = $this->processDivisionBetween($parser, $operator_token, $left_operand, $right_operand);
					if($replacement === null){
						continue;
					}

					array_splice($left_tree, $i, 1, $replacement[0]);
					array_splice($right_tree, $j, 1, $replacement[1]);
					$changed = true;
					++$changes;
					break 2;
				}
			}
		}while($changed);

		if($changes === 0){
			return null;
		}

		return [...$left_tree, ...$right_tree, $operator_token];
	}

	/**
	 * @param Parser $parser
	 * @param FunctionCallExpressionToken $operator_token
	 * @param ExpressionToken[] $left_operand
	 * @param ExpressionToken[] $right_operand
	 * @return array{ExpressionToken[], ExpressionToken[]}|null
	 */
	private function processDivisionBetween(Parser $parser, FunctionCallExpressionToken $operator_token, array $left_operand, array $right_operand) : ?array{
		// reduce (x / x) to (1 / 1)
		if($this->tokensEqualByReturnValue($left_operand, $right_operand)){
			return [
				// on cancelling a value in the numerator with a value in the denominator, replace them operands with 1 (identity element of division)
				[new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens($left_operand), 1)],
				[new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens($right_operand), 1)]
			];
		}

		// reduce (x ** y / x ** z) to {[x ** (y - z)] / 1}
		[$left_tree, $right_tree] = Util::expressionTokenArrayToTree([...$left_operand, ...$right_operand]);
		$binary_operator_registry = $parser->getBinaryOperatorRegistry();
		$e_op = $binary_operator_registry->get("**");
		if(
			is_array($left_tree) &&
			is_array($right_tree) &&

			count($left_tree) === 3 &&
			count($left_tree) === count($right_tree) &&

			$left_tree[2] instanceof FunctionCallExpressionToken &&
			$left_tree[2]->parent instanceof BinaryOperatorToken &&
			$binary_operator_registry->get($left_tree[2]->parent->getOperator()) === $e_op &&

			$right_tree[2] instanceof FunctionCallExpressionToken &&
			$right_tree[2]->parent instanceof BinaryOperatorToken &&
			$binary_operator_registry->get($right_tree[2]->parent->getOperator()) === $e_op
		){
			$lvalue = $left_tree[0];
			if(is_array($lvalue)){
				Util::flattenArray($lvalue);
			}else{
				$lvalue = [$lvalue];
			}

			$rvalue = $right_tree[0];
			if(is_array($rvalue)){
				Util::flattenArray($rvalue);
			}else{
				$rvalue = [$rvalue];
			}

			if($this->tokensEqualByReturnValue($lvalue, $rvalue)){
				$s_op = $binary_operator_registry->get("-");
				$left = [
					$lvalue,
					[
						$left_tree[1],
						$right_tree[1],
						new FunctionCallExpressionToken(
							Util::positionContainingExpressionTokens([...$left_operand, ...$right_operand]),
							$s_op->getSymbol(),
							2,
							$s_op->getOperator(),
							$s_op->isDeterministic(),
							$s_op->isCommutative(),
							new BinaryOperatorToken($operator_token->getPos(), $s_op->getSymbol())
						)
					],
					new FunctionCallExpressionToken(
						Util::positionContainingExpressionTokens([...$left_operand, ...$right_operand]),
						$e_op->getSymbol(),
						2,
						$e_op->getOperator(),
						$e_op->isDeterministic(),
						$e_op->isCommutative(),
						new BinaryOperatorToken($operator_token->getPos(), $e_op->getSymbol())
					)
				];
				Util::flattenArray($left);
				return [
					$left,
					[new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens($right_operand), 1)]
				];
			}
		}

		return null;
	}
}
