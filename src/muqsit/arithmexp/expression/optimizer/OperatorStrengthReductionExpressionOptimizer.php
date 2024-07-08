<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\optimizer;

use muqsit\arithmexp\expression\ConstantExpression;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\expression\token\OpcodeExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\pattern\matcher\AnyPatternMatcher;
use muqsit\arithmexp\pattern\matcher\ArrayPatternMatcher;
use muqsit\arithmexp\pattern\matcher\OpcodePatternMatcher;
use muqsit\arithmexp\pattern\matcher\PatternMatcher;
use muqsit\arithmexp\pattern\Pattern;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\OpcodeToken;
use muqsit\arithmexp\token\UnaryOperatorToken;
use muqsit\arithmexp\Util;
use RuntimeException;
use function array_filter;
use function array_splice;
use function count;
use function gettype;
use function is_array;
use function is_float;
use function is_int;
use function is_nan;
use const NAN;

final class OperatorStrengthReductionExpressionOptimizer implements ExpressionOptimizer{

	readonly private PatternMatcher $any_non_numeric_matcher;
	readonly private PatternMatcher $binary_operation_matcher;
	readonly private PatternMatcher $unary_operation_matcher;
	readonly private PatternMatcher $modulo_operation_matcher;
	readonly private PatternMatcher $multiplication_operation_matcher;
	readonly private PatternMatcher $exponentiation_operation_matcher;

	public function __construct(){
		$this->any_non_numeric_matcher = Pattern::not(Pattern::instanceof(NumericLiteralExpressionToken::class));
		$this->binary_operation_matcher = new ArrayPatternMatcher([
			AnyPatternMatcher::instance(),
			AnyPatternMatcher::instance(),
			OpcodePatternMatcher::setOf([
				OpcodeToken::OP_BINARY_ADD,
				OpcodeToken::OP_BINARY_DIV,
				OpcodeToken::OP_BINARY_EXP,
				OpcodeToken::OP_BINARY_MOD,
				OpcodeToken::OP_BINARY_MUL,
				OpcodeToken::OP_BINARY_SUB
			])
		]);
		$this->unary_operation_matcher = new ArrayPatternMatcher([
			AnyPatternMatcher::instance(),
			OpcodePatternMatcher::setOf([OpcodeToken::OP_UNARY_NVE, OpcodeToken::OP_UNARY_PVE])
		]);
		$this->modulo_operation_matcher = new ArrayPatternMatcher([
			AnyPatternMatcher::instance(),
			AnyPatternMatcher::instance(),
			OpcodePatternMatcher::setOf([OpcodeToken::OP_BINARY_MOD])
		]);
		$this->multiplication_operation_matcher = new ArrayPatternMatcher([
			AnyPatternMatcher::instance(),
			AnyPatternMatcher::instance(),
			OpcodePatternMatcher::setOf([OpcodeToken::OP_BINARY_MUL])
		]);
		$this->exponentiation_operation_matcher = new ArrayPatternMatcher([
			AnyPatternMatcher::instance(),
			AnyPatternMatcher::instance(),
			OpcodePatternMatcher::setOf([OpcodeToken::OP_BINARY_EXP])
		]);
	}

	/**
	 * @param ExpressionToken|list<ExpressionToken|list<ExpressionToken>> $entry
	 * @return list<ExpressionToken>
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
		$postfix_expression_tokens = Util::expressionTokenArrayToTree($parser, $expression->getPostfixExpressionTokens());
		$filter = static fn(ExpressionToken $token) : bool => !$token->isDeterministic();

		$changes = 0;
		/** @var array{ExpressionToken|list<ExpressionToken>, ExpressionToken|list<ExpressionToken>, OpcodeExpressionToken} $entry */
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

		/** @var array{ExpressionToken|list<ExpressionToken>, OpcodeExpressionToken} $entry */
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
	 * @param list<ExpressionToken> $tokens
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
	 * @param list<ExpressionToken> $tokens
	 * @return bool
	 */
	private function valueIsNan(array $tokens) : bool{
		return count($tokens) === 1 && $tokens[0] instanceof NumericLiteralExpressionToken && is_nan($tokens[0]->value);
	}

	/**
	 * @param list<ExpressionToken> $x
	 * @param list<ExpressionToken> $y
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
	 * @param Position $position
	 * @param OpcodeToken::OP_* $code
	 * @return OpcodeExpressionToken
	 */
	private function buildOpcodeToken(Parser $parser, Position $position, int $code) : OpcodeExpressionToken{
		$manager = $parser->operator_manager;
		$symbol = OpcodeToken::opcodeToString($code);
		return match($code){
			OpcodeToken::OP_BINARY_ADD, OpcodeToken::OP_BINARY_DIV, OpcodeToken::OP_BINARY_EXP, OpcodeToken::OP_BINARY_MOD, OpcodeToken::OP_BINARY_MUL, OpcodeToken::OP_BINARY_SUB => new OpcodeExpressionToken($position, $code, new BinaryOperatorToken($position, $manager->binary_registry->get($symbol)->getSymbol())),
			OpcodeToken::OP_UNARY_NVE, OpcodeToken::OP_UNARY_PVE => new OpcodeExpressionToken($position, $code, new UnaryOperatorToken($position, $manager->unary_registry->get($symbol)->getSymbol()))
		};
	}

	/**
	 * @param Parser $parser
	 * @param OpcodeExpressionToken $operator_token
	 * @param list<ExpressionToken> $operand
	 * @return list<ExpressionToken>|null
	 */
	private function processUnaryExpression(Parser $parser, OpcodeExpressionToken $operator_token, array $operand) : ?array{
		return match($operator_token->code){
			OpcodeToken::OP_UNARY_PVE => $operand,
			OpcodeToken::OP_UNARY_NVE => [
				new NumericLiteralExpressionToken($operator_token->getPos(), -1),
				...$operand,
				$this->buildOpcodeToken($parser, Util::positionContainingExpressionTokens([...$operand, $operator_token]), OpcodeToken::OP_BINARY_MUL)
			],
			default => null
		};
	}

	/**
	 * @param Parser $parser
	 * @param Expression $expression
	 * @param OpcodeExpressionToken $operator_token
	 * @param list<ExpressionToken> $left
	 * @param list<ExpressionToken> $right
	 * @return list<ExpressionToken>|null
	 * @throws ParseException
	 */
	private function processBinaryExpression(Parser $parser, Expression $expression, OpcodeExpressionToken $operator_token, array $left, array $right) : ?array{
		return match($operator_token->code){
			OpcodeToken::OP_BINARY_EXP => match(true){
				$this->valueEquals($left, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueEquals($left, 1) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 1)],
				$this->valueEquals($right, 2) && !$this->valueEquals($left, 2) => [
					...$left,
					...$left,
					$this->buildOpcodeToken($parser, Util::positionContainingExpressionTokens($right), OpcodeToken::OP_BINARY_MUL)
				],
				$this->valueEquals($right, 1) => $left,
				$this->valueEquals($right, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 1)],
				default => null
			},
			OpcodeToken::OP_BINARY_MUL => match(true){
				$this->valueEquals($left, 1) => $right,
				$this->valueEquals($right, 1) => $left,
				$this->valueEquals($left, 0), $this->valueEquals($right, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueIsNan($left), $this->valueIsNan($right) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), NAN)],
				default => null
			},
			OpcodeToken::OP_BINARY_DIV => match(true){
				$this->valueEquals($right, 0) => throw ParseException::unresolvableExpressionDivisionByZero($expression->getExpression(), Util::positionContainingExpressionTokens($right)),
				$this->valueEquals($left, 0) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueEquals($right, 1) => $left,
				$this->valueIsNan($right) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), NAN)],
				default => $this->processDivision($parser, $expression, $operator_token, $left, $right)
			},
			OpcodeToken::OP_BINARY_MOD => match(true){
				$this->valueEquals($right, 0) => throw ParseException::unresolvableExpressionModuloByZero($expression->getExpression(), Util::positionContainingExpressionTokens($right)),
				$this->valueEquals($right, 1) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				default => $this->processModulo($parser, $expression, $operator_token, $left, $right)
			},
			OpcodeToken::OP_BINARY_ADD => match(true){
				$this->valueEquals($left, 0) => $right,
				$this->valueEquals($right, 0) => $left,
				$this->valueIsNan($left), $this->valueIsNan($right) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), NAN)],
				default => $this->processAddition($parser, $operator_token, $left, $right)
			},
			OpcodeToken::OP_BINARY_SUB => match(true){
				$this->tokensEqualByReturnValue($left, $right) && $this->any_non_numeric_matcher->matches([...$left, ...$right]) => [new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens([...$left, ...$right]), 0)],
				$this->valueEquals($left, 0) => [
					new NumericLiteralEXpressionToken(Util::positionContainingExpressionTokens($right), -1),
					...$right,
					$this->buildOpcodeToken($parser, Util::positionContainingExpressionTokens($right), OpcodeToken::OP_BINARY_MUL)
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
	 * @param OpcodeExpressionToken $operator_token
	 * @param list<ExpressionToken> $left
	 * @param list<ExpressionToken> $right
	 * @return list<ExpressionToken>|null
	 */
	private function processAddition(Parser $parser, OpcodeExpressionToken $operator_token, array $left, array $right) : ?array{
		$filter = $this->multiplication_operation_matcher->matches(...);

		$left_tree = Util::expressionTokenArrayToTree($parser, $left);
		Util::flattenArray($left_tree, $filter);

		foreach($left_tree as $index => $left_operand){
			if($left_operand instanceof NumericLiteralExpressionToken && $left_operand->value < 0){
				$left_tree[$index] = new NumericLiteralExpressionToken($left_operand->getPos(), -$left_operand->value);
				return [
					...$right,
					...$this->flattened($left_tree),
					$this->buildOpcodeToken($parser, $operator_token->getPos(), OpcodeToken::OP_BINARY_SUB)
				];
			}
		}

		$right_tree = Util::expressionTokenArrayToTree($parser, $right);
		Util::flattenArray($right_tree, $filter);
		foreach($right_tree as $index => $right_operand){
			if($right_operand instanceof NumericLiteralExpressionToken && $right_operand->value < 0){
				$right_tree[$index] = new NumericLiteralExpressionToken($right_operand->getPos(), -$right_operand->value);
				return [
					...$left,
					...$this->flattened($right_tree),
					$this->buildOpcodeToken($parser, $operator_token->getPos(), OpcodeToken::OP_BINARY_SUB)
				];
			}
		}

		return null;
	}

	/**
	 * @param Parser $parser
	 * @param OpcodeExpressionToken $operator_token
	 * @param list<ExpressionToken> $left
	 * @param list<ExpressionToken> $right
	 * @return list<ExpressionToken>|null
	 */
	private function processSubtraction(Parser $parser, OpcodeExpressionToken $operator_token, array $left, array $right) : ?array{
		$filter = $this->multiplication_operation_matcher->matches(...);

		$left_tree = Util::expressionTokenArrayToTree($parser, $left);
		Util::flattenArray($left_tree, $filter);

		// -x - y = -(x + y)
		foreach($left_tree as $index => $left_operand){
			if($left_operand instanceof NumericLiteralExpressionToken && $left_operand->value < 0){
				$left_tree[$index] = new NumericLiteralExpressionToken($left_operand->getPos(), -$left_operand->value);
				return [
					new NumericLiteralExpressionToken($left_operand->getPos(), -1),
					...$this->flattened([
						$left_tree,
						$right,
						$this->buildOpcodeToken($parser, $operator_token->getPos(), OpcodeToken::OP_BINARY_ADD)
					]),
					$this->buildOpcodeToken($parser, $operator_token->getPos(), OpcodeToken::OP_BINARY_MUL)
				];
			}
		}

		// x - -y = x + y
		$right_tree = Util::expressionTokenArrayToTree($parser, $right);
		Util::flattenArray($right_tree, $filter);
		foreach($right_tree as $index => $right_operand){
			if($right_operand instanceof NumericLiteralExpressionToken && $right_operand->value < 0){
				$right_tree[$index] = new NumericLiteralExpressionToken($right_operand->getPos(), -$right_operand->value);
				return [
					...$left,
					...$this->flattened($right_tree),
					$this->buildOpcodeToken($parser, $operator_token->getPos(), OpcodeToken::OP_BINARY_ADD)
				];
			}
		}

		return null;
	}

	/**
	 * @param Parser $parser
	 * @param Expression $expression
	 * @param OpcodeExpressionToken $operator_token
	 * @param list<ExpressionToken> $left
	 * @param list<ExpressionToken> $right
	 * @return list<ExpressionToken>|null
	 */
	private function processModulo(Parser $parser, Expression $expression, OpcodeExpressionToken $operator_token, array $left, array $right) : ?array{
		// reduce (x % y) % y to (x % y)
		[$left_tree] = Util::expressionTokenArrayToTree($parser, $left);
		if(is_array($left_tree) && $this->modulo_operation_matcher->matches($left_tree)){
			$left_rvalue = $this->flattened($left_tree[1]);
			if($this->tokensEqualByReturnValue($left_rvalue, $right)){
				return $left;
			}
		}

		return null;
	}

	/**
	 * @param Parser $parser
	 * @param Expression $expression
	 * @param OpcodeExpressionToken $operator_token
	 * @param list<ExpressionToken> $left
	 * @param list<ExpressionToken> $right
	 * @return list<ExpressionToken>|null
	 */
	private function processDivision(Parser $parser, Expression $expression, OpcodeExpressionToken $operator_token, array $left, array $right) : ?array{
		$filter = $this->multiplication_operation_matcher->matches(...);

		$left_tree = Util::expressionTokenArrayToTree($parser, $left);
		Util::flattenArray($left_tree, $filter);
		/** @var list<ExpressionToken> $left_tree */

		$right_tree = Util::expressionTokenArrayToTree($parser, $right);
		Util::flattenArray($right_tree, $filter);
		/** @var list<ExpressionToken> $right_tree */

		$changes = 0;
		do{
			$changed = false;
			foreach($left_tree as $i => $left_operand){
				if($left_operand instanceof OpcodeExpressionToken || ($left_operand instanceof NumericLiteralExpressionToken && $left_operand->value === 1)){
					continue;
				}

				$left_operand = $this->flattened($left_operand);
				foreach($right_tree as $j => $right_operand){
					if($right_operand instanceof OpcodeExpressionToken || ($right_operand instanceof NumericLiteralExpressionToken && $right_operand->value === 1)){
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
	 * @param OpcodeExpressionToken $operator_token
	 * @param list<ExpressionToken> $left_operand
	 * @param list<ExpressionToken> $right_operand
	 * @return array{list<ExpressionToken>, list<ExpressionToken>}|null
	 */
	private function processDivisionBetween(Parser $parser, Expression $expression, OpcodeExpressionToken $operator_token, array $left_operand, array $right_operand) : ?array{
		// reduce (n1 / n2) to (n / 1) where n1 and n2 are numeric, and n = n1 / n2
		if(
			count($left_operand) === 1 &&
			$left_operand[0] instanceof NumericLiteralExpressionToken &&
			count($right_operand) === 1 &&
			$right_operand[0] instanceof NumericLiteralExpressionToken &&
			($result = ConstantFoldingExpressionOptimizer::evaluateDeterministicTokens($parser, $expression, $operator_token, [...$left_operand, ...$right_operand])) !== null &&
			(is_int($result) || is_float($result))
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
		 * @var list<ExpressionToken> $left_tree
		 * @var list<ExpressionToken> $right_tree
		 */
		[$left_tree, $right_tree] = Util::expressionTokenArrayToTree($parser, [...$left_operand, ...$right_operand]);
		if($this->exponentiation_operation_matcher->matches($left_tree) && $this->exponentiation_operation_matcher->matches($right_tree)){
			$lvalue = $this->flattened($left_tree[0]);
			$rvalue = $this->flattened($right_tree[0]);
			if($this->tokensEqualByReturnValue($lvalue, $rvalue)){
				return [
					$this->flattened([
						$lvalue,
						[
							$left_tree[1],
							$right_tree[1],
							$this->buildOpcodeToken($parser, Util::positionContainingExpressionTokens([...$lvalue, ...$rvalue]), OpcodeToken::OP_BINARY_SUB)
						],
						$this->buildOpcodeToken($parser, $operator_token->getPos(), OpcodeToken::OP_BINARY_EXP)
					]),
					[new NumericLiteralExpressionToken(Util::positionContainingExpressionTokens($right_operand), 1)]
				];
			}
		}

		return null;
	}
}
