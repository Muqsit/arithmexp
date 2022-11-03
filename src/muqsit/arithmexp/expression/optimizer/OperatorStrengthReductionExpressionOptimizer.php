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
use muqsit\arithmexp\pattern\matcher\AnyPatternMatcher;
use muqsit\arithmexp\pattern\matcher\ArrayPatternMatcher;
use muqsit\arithmexp\pattern\matcher\BinaryOperatorPatternMatcher;
use muqsit\arithmexp\pattern\matcher\PatternMatcher;
use muqsit\arithmexp\pattern\matcher\UnaryOperatorPatternMatcher;
use muqsit\arithmexp\pattern\Pattern;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\UnaryOperatorToken;
use muqsit\arithmexp\Util;
use RuntimeException;
use function array_filter;
use function array_splice;
use function assert;
use function count;
use function gettype;
use function is_array;
use function is_nan;
use const NAN;

final class OperatorStrengthReductionExpressionOptimizer implements ExpressionOptimizer{

	private PatternMatcher $any_non_numeric_matcher;
	private PatternMatcher $binary_operation_matcher;
	private PatternMatcher $unary_operation_matcher;
	private PatternMatcher $multiplication_operation_matcher;
	private PatternMatcher $exponentiation_operation_matcher;

	public function __construct(){
		$this->any_non_numeric_matcher = Pattern::not(Pattern::instanceof(NumericLiteralExpressionToken::class));
		$this->binary_operation_matcher = new ArrayPatternMatcher([
			AnyPatternMatcher::instance(),
			AnyPatternMatcher::instance(),
			BinaryOperatorPatternMatcher::setOf(["**", "*", "/", "+", "-", "%"])
		]);
		$this->unary_operation_matcher = new ArrayPatternMatcher([
			AnyPatternMatcher::instance(),
			UnaryOperatorPatternMatcher::setOf(["+", "-"])
		]);
		$this->multiplication_operation_matcher = new ArrayPatternMatcher([
			AnyPatternMatcher::instance(),
			AnyPatternMatcher::instance(),
			BinaryOperatorPatternMatcher::setOf(["*"])
		]);
		$this->exponentiation_operation_matcher = new ArrayPatternMatcher([
			AnyPatternMatcher::instance(),
			AnyPatternMatcher::instance(),
			BinaryOperatorPatternMatcher::setOf(["**"])
		]);
	}

	/**
	 * @param ExpressionToken|ExpressionToken[]|ExpressionToken[][] $entry
	 * @return ExpressionToken[]
	 */
	private function flattened(array|ExpressionToken $entry) : array{
		if(is_array($entry)){
			Util::flattenArray($entry);
		}else{
			$entry = [$entry];
		}
		return $entry;
	}

	public function run(Parser $parser, Expression $expression) : Expression{
		$postfix_expression_tokens = Util::expressionTokenArrayToTree($expression->getPostfixExpressionTokens());
		$filter = static fn(ExpressionToken $token) : bool => !$token->isDeterministic();

		$changes = 0;
		/** @var array{ExpressionToken|ExpressionToken[], ExpressionToken|ExpressionToken[], FunctionCallExpressionToken} $entry */
		foreach(Pattern::findMatching($this->binary_operation_matcher, $postfix_expression_tokens) as &$entry){
			$left = $this->flattened($entry[0]);
			$right = $this->flattened($entry[1]);
			if(count(array_filter([...$left, ...$right], $filter)) === 0){
				continue;
			}

			$replacement = $this->processBinaryExpression($parser, $expression, $entry[2], $left, $right);
			if($replacement !== null){
				$entry = $replacement;
				++$changes;
			}
		}
		unset($entry);

		/** @var array{ExpressionToken|ExpressionToken[], FunctionCallExpressionToken} $entry */
		foreach(Pattern::findMatching($this->unary_operation_matcher, $postfix_expression_tokens) as &$entry){
			$operand = $this->flattened($entry[0]);
			if(count(array_filter($operand, $filter)) === 0){
				continue;
			}

			$replacement = $this->processUnaryExpression($parser, $entry[1], $operand);
			if($replacement !== null){
				$entry = $replacement;
				++$changes;
			}
		}
		unset($entry);

		Util::flattenArray($postfix_expression_tokens);
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
	 * @param ExpressionToken[] $tokens
	 * @return bool
	 */
	private function valueIsNan(array $tokens) : bool{
		return count($tokens) === 1 && $tokens[0] instanceof NumericLiteralExpressionToken && is_nan($tokens[0]->value);
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
	 * @param ExpressionToken[] $operand
	 * @return ExpressionToken[]|null
	 */
	private function processUnaryExpression(Parser $parser, FunctionCallExpressionToken $operator_token, array $operand) : ?array{
		$token = $operator_token->parent;
		assert($token instanceof UnaryOperatorToken);
		$m_op = $parser->getOperatorManager()->getBinaryRegistry()->get("*");
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
	 * @param ExpressionToken[] $left
	 * @param ExpressionToken[] $right
	 * @return ExpressionToken[]|null
	 * @throws ParseException
	 */
	private function processBinaryExpression(Parser $parser, Expression $expression, FunctionCallExpressionToken $operator_token, array $left, array $right) : ?array{
		$token = $operator_token->parent;
		assert($token instanceof BinaryOperatorToken);
		$m_op = $parser->getOperatorManager()->getBinaryRegistry()->get("*");
		return match($token->getOperator()){
			"**" => match(true){
				$this->valueEquals($left, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueEquals($left, 1) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 1)],
				$this->valueEquals($right, 2) && !$this->valueEquals($left, 2) => [
					...$left,
					...$left,
					new FunctionCallExpressionToken(Util::positionContainingExpressionTokens($right), $m_op->getSymbol(), 2, $m_op->getOperator(), $m_op->isDeterministic(), $m_op->isCommutative(), new BinaryOperatorToken($token->getPos(), $m_op->getSymbol()))
				],
				$this->valueEquals($right, 1) => $left,
				$this->valueEquals($right, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 1)],
				default => null
			},
			"*" => match(true){
				$this->valueEquals($left, 1) => $right,
				$this->valueEquals($right, 1) => $left,
				$this->valueEquals($left, 0), $this->valueEquals($right, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueIsNan($left), $this->valueIsNan($right) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), NAN)],
				default => null
			},
			"/" => match(true){
				$this->valueEquals($right, 0) => throw ParseException::unresolvableExpressionDivisionByZero($expression->getExpression(), Util::positionContainingExpressionTokens($right)),
				$this->valueEquals($left, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueEquals($right, 1) => $left,
				$this->valueIsNan($right) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), NAN)],
				default => $this->processDivision($parser, $expression, $operator_token, $left, $right)
			},
			"%" => match(true){
				$this->valueEquals($right, 0) => throw ParseException::unresolvableExpressionModuloByZero($expression->getExpression(), Util::positionContainingExpressionTokens($right)),
				$this->valueEquals($right, 1) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				default => null
			},
			"+" => match(true){
				$this->valueEquals($left, 0) => $right,
				$this->valueEquals($right, 0) => $left,
				$this->valueIsNan($left), $this->valueIsNan($right) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), NAN)],
				default => $this->processAddition($parser, $operator_token, $left, $right)
			},
			"-" => match(true){
				$this->tokensEqualByReturnValue($left, $right) && $this->any_non_numeric_matcher->matches([...$left, ...$right]) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueEquals($left, 0) => [
					new NumericLiteralEXpressionToken(Util::positionContainingExpressionTokens($right), -1),
					...$right,
					new FunctionCallExpressionToken(Util::positionContainingExpressionTokens($right), $m_op->getSymbol(), 2, $m_op->getOperator(), $m_op->isDeterministic(), $m_op->isCommutative(), new BinaryOperatorToken($token->getPos(), $m_op->getSymbol()))
				],
				$this->valueEquals($right, 0) => $left,
				$this->valueIsNan($left), $this->valueIsNan($right) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), NAN)],
				default => $this->processSubtraction($parser, $operator_token, $left, $right)
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
	private function processAddition(Parser $parser, FunctionCallExpressionToken $operator_token, array $left, array $right) : ?array{
		$filter = fn(array $array) : bool => $this->multiplication_operation_matcher->matches($array);

		$left_tree = Util::expressionTokenArrayToTree($left);
		Util::flattenArray($left_tree, $filter);

		foreach($left_tree as $index => $left_operand){
			if($left_operand instanceof NumericLiteralExpressionToken && $left_operand->value < 0){
				$left_tree[$index] = new NumericLiteralExpressionToken($left_operand->getPos(), -$left_operand->value);
				$s_op = $parser->getOperatorManager()->getBinaryRegistry()->get("-");
				return [
					...$right,
					...$this->flattened($left_tree),
					new FunctionCallExpressionToken($operator_token->getPos(), $s_op->getSymbol(), 2, $s_op->getOperator(), $s_op->isDeterministic(), $s_op->isCommutative(), new BinaryOperatorToken($operator_token->getPos(), $s_op->getSymbol()))
				];
			}
		}

		$right_tree = Util::expressionTokenArrayToTree($right);
		Util::flattenArray($right_tree, $filter);
		foreach($right_tree as $index => $right_operand){
			if($right_operand instanceof NumericLiteralExpressionToken && $right_operand->value < 0){
				$right_tree[$index] = new NumericLiteralExpressionToken($right_operand->getPos(), -$right_operand->value);
				$s_op = $parser->getOperatorManager()->getBinaryRegistry()->get("-");
				return [
					...$left,
					...$this->flattened($right_tree),
					new FunctionCallExpressionToken($operator_token->getPos(), $s_op->getSymbol(), 2, $s_op->getOperator(), $s_op->isDeterministic(), $s_op->isCommutative(), new BinaryOperatorToken($operator_token->getPos(), $s_op->getSymbol()))
				];
			}
		}


		return null;
	}

	/**
	 * @param Parser $parser
	 * @param FunctionCallExpressionToken $operator_token
	 * @param ExpressionToken[] $left
	 * @param ExpressionToken[] $right
	 * @return ExpressionToken[]|null
	 */
	private function processSubtraction(Parser $parser, FunctionCallExpressionToken $operator_token, array $left, array $right) : ?array{
		$filter = fn(array $array) : bool => $this->multiplication_operation_matcher->matches($array);

		$left_tree = Util::expressionTokenArrayToTree($left);
		Util::flattenArray($left_tree, $filter);

		// -x - y = -(x + y)
		foreach($left_tree as $index => $left_operand){
			if($left_operand instanceof NumericLiteralExpressionToken && $left_operand->value < 0){
				$left_tree[$index] = new NumericLiteralExpressionToken($left_operand->getPos(), -$left_operand->value);
				$a_op = $parser->getOperatorManager()->getBinaryRegistry()->get("+");
				$m_op = $parser->getOperatorManager()->getBinaryRegistry()->get("*");
				return [
					new NumericLiteralExpressionToken($left_operand->getPos(), -1),
					...$this->flattened([
						$left_tree,
						$right,
						new FunctionCallExpressionToken($operator_token->getPos(), $a_op->getSymbol(), 2, $a_op->getOperator(), $a_op->isDeterministic(), $a_op->isCommutative(), new BinaryOperatorToken($operator_token->getPos(), $a_op->getSymbol()))
					]),
					new FunctionCallExpressionToken($operator_token->getPos(), $m_op->getSymbol(), 2, $m_op->getOperator(), $m_op->isDeterministic(), $m_op->isCommutative(), new BinaryOperatorToken($operator_token->getPos(), $m_op->getSymbol()))
				];
			}
		}

		// x - -y = x + y
		$right_tree = Util::expressionTokenArrayToTree($right);
		Util::flattenArray($right_tree, $filter);
		foreach($right_tree as $index => $right_operand){
			if($right_operand instanceof NumericLiteralExpressionToken && $right_operand->value < 0){
				$right_tree[$index] = new NumericLiteralExpressionToken($right_operand->getPos(), -$right_operand->value);
				$a_op = $parser->getOperatorManager()->getBinaryRegistry()->get("+");
				return [
					...$left,
					...$this->flattened($right_tree),
					new FunctionCallExpressionToken($operator_token->getPos(), $a_op->getSymbol(), 2, $a_op->getOperator(), $a_op->isDeterministic(), $a_op->isCommutative(), new BinaryOperatorToken($operator_token->getPos(), $a_op->getSymbol()))
				];
			}
		}


		return null;
	}

	/**
	 * @param Parser $parser
	 * @param Expression $expression
	 * @param FunctionCallExpressionToken $operator_token
	 * @param ExpressionToken[] $left
	 * @param ExpressionToken[] $right
	 * @return ExpressionToken[]|null
	 */
	private function processDivision(Parser $parser, Expression $expression, FunctionCallExpressionToken $operator_token, array $left, array $right) : ?array{
		$filter = fn(array $array) : bool => $this->multiplication_operation_matcher->matches($array);

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

				$left_operand = $this->flattened($left_operand);
				foreach($right_tree as $j => $right_operand){
					if($right_operand instanceof FunctionCallExpressionToken || ($right_operand instanceof NumericLiteralExpressionToken && $right_operand->value === 1)){
						continue;
					}

					$right_operand = $this->flattened($right_operand);
					$replacement = $this->processDivisionBetween($parser, $expression, $operator_token, $left_operand, $right_operand);
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
		return $changes > 0 ? [...$left_tree, ...$right_tree, $operator_token] : null;
	}

	/**
	 * @param Parser $parser
	 * @param Expression $expression
	 * @param FunctionCallExpressionToken $operator_token
	 * @param ExpressionToken[] $left_operand
	 * @param ExpressionToken[] $right_operand
	 * @return array{ExpressionToken[], ExpressionToken[]}|null
	 */
	private function processDivisionBetween(Parser $parser, Expression $expression, FunctionCallExpressionToken $operator_token, array $left_operand, array $right_operand) : ?array{
		// reduce (n1 / n2) to (n / 1) where n1 and n2 are numeric, and n = n1 / n2
		if(
			count($left_operand) === 1 &&
			$left_operand[0] instanceof NumericLiteralExpressionToken &&
			count($right_operand) === 1 &&
			$right_operand[0] instanceof NumericLiteralExpressionToken &&
			($result = ConstantFoldingExpressionOptimizer::evaluateFunctionCallTokens($expression, $operator_token, [...$left_operand, ...$right_operand])) !== null
		){
			return [
				[new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens($left_operand), $result)],
				[new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens($right_operand), 1)]
			];
		}

		// reduce (x / x) to (1 / 1)
		if($this->tokensEqualByReturnValue($left_operand, $right_operand)){
			return [
				// on cancelling a value in the numerator with a value in the denominator, replace them operands with 1 (identity element of division)
				[new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens($left_operand), 1)],
				[new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens($right_operand), 1)]
			];
		}

		// reduce (x ** y / x ** z) to {[x ** (y - z)] / 1}
		/**
		 * @var ExpressionToken[] $left_tree
		 * @var ExpressionToken[] $right_tree
		 */
		[$left_tree, $right_tree] = Util::expressionTokenArrayToTree([...$left_operand, ...$right_operand]);
		if($this->exponentiation_operation_matcher->matches($left_tree) && $this->exponentiation_operation_matcher->matches($right_tree)){
			$lvalue = $this->flattened($left_tree[0]);
			$rvalue = $this->flattened($right_tree[0]);
			if($this->tokensEqualByReturnValue($lvalue, $rvalue)){
				$binary_operator_registry = $parser->getOperatorManager()->getBinaryRegistry();
				$e_op = $binary_operator_registry->get("**");
				$s_op = $binary_operator_registry->get("-");
				return [
					$this->flattened([
						$lvalue,
						[
							$left_tree[1],
							$right_tree[1],
							new FunctionCallExpressionToken(Util::positionContainingExpressionTokens([...$lvalue, ...$rvalue]), $s_op->getSymbol(), 2, $s_op->getOperator(), $s_op->isDeterministic(), $s_op->isCommutative(), new BinaryOperatorToken($operator_token->getPos(), $s_op->getSymbol()))
						],
						new FunctionCallExpressionToken($operator_token->getPos(), $e_op->getSymbol(), 2, $e_op->getOperator(), $e_op->isDeterministic(), $e_op->isCommutative(), new BinaryOperatorToken($operator_token->getPos(), $e_op->getSymbol()))
					]),
					[new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens($right_operand), 1)]
				];
			}
		}

		return null;
	}
}
