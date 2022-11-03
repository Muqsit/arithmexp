<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use InvalidArgumentException;
use muqsit\arithmexp\expression\ConstantRegistry;
use muqsit\arithmexp\expression\Expression;
use muqsit\arithmexp\expression\optimizer\ExpressionOptimizerRegistry;
use muqsit\arithmexp\expression\RawExpression;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\function\FunctionRegistry;
use muqsit\arithmexp\operator\binary\BinaryOperatorRegistry;
use muqsit\arithmexp\operator\OperatorManager;
use muqsit\arithmexp\operator\unary\UnaryOperatorRegistry;
use muqsit\arithmexp\token\BinaryOperatorToken;
use muqsit\arithmexp\token\FunctionCallArgumentSeparatorToken;
use muqsit\arithmexp\token\FunctionCallToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use muqsit\arithmexp\token\ParenthesisToken;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\token\UnaryOperatorToken;
use RuntimeException;
use function array_filter;
use function array_key_last;
use function array_shift;
use function array_slice;
use function array_splice;
use function array_unshift;
use function assert;
use function count;
use function is_array;
use function min;

final class Parser{

	public static function createDefault() : self{
		$operator_manager = OperatorManager::createDefault();
		return new self(
			$operator_manager,
			ConstantRegistry::createDefault(),
			FunctionRegistry::createDefault(),
			ExpressionOptimizerRegistry::createDefault(),
			Scanner::createDefault($operator_manager)
		);
	}

	public static function createUnoptimized() : self{
		$default = self::createDefault();
		return new self(
			$default->operator_manager,
			$default->constant_registry,
			$default->function_registry,
			new ExpressionOptimizerRegistry(),
			$default->scanner
		);
	}

	public function __construct(
		private OperatorManager $operator_manager,
		private ConstantRegistry $constant_registry,
		private FunctionRegistry $function_registry,
		private ExpressionOptimizerRegistry $expression_optimizer_registry,
		private Scanner $scanner
	){}

	public function getOperatorManager() : OperatorManager{
		return $this->operator_manager;
	}

	public function getConstantRegistry() : ConstantRegistry{
		return $this->constant_registry;
	}

	public function getFunctionRegistry() : FunctionRegistry{
		return $this->function_registry;
	}

	public function getExpressionOptimizerRegistry() : ExpressionOptimizerRegistry{
		return $this->expression_optimizer_registry;
	}

	public function getScanner() : Scanner{
		return $this->scanner;
	}

	/**
	 * Parses a given mathematical expression for runtime evaluation.
	 *
	 * @param string $expression
	 * @return Expression
	 * @throws ParseException
	 */
	public function parse(string $expression) : Expression{
		$tokens = $this->scanner->scan($expression);
		$this->processTokens($expression, $tokens);
		$this->convertTokenTreeToPostfixTokenTree($tokens);

		$postfix_expression_tokens = [];
		foreach($tokens as $token){
			$replacement = $token->toExpressionToken($this, $expression);
			if($replacement instanceof FunctionCallExpressionToken){
				$parameters = array_slice(Util::expressionTokenArrayToTree($postfix_expression_tokens, 0, count($postfix_expression_tokens)), -$replacement->argument_count);
				Util::flattenArray($parameters);
				$pos = Position::containing([Util::positionContainingExpressionTokens($parameters), $token->getPos()]);
				$replacement = new FunctionCallExpressionToken($pos, $replacement->name, $replacement->argument_count, $replacement->function, $replacement->deterministic, $replacement->commutative, $replacement->parent);
			}
			$postfix_expression_tokens[] = $replacement;
		}

		$result = new RawExpression($expression, $postfix_expression_tokens);
		do{
			$tokens_before = $result->getPostfixExpressionTokens();
			foreach($this->expression_optimizer_registry->getRegistered() as $optimizer){
				$result = $optimizer->run($this, $result);
			}
		}while($tokens_before !== $result->getPostfixExpressionTokens());
		return $result;
	}

	/**
	 * Transforms a given token array in-place by reshaping it into a processed
	 * token tree.
	 *
	 * @param string $expression
	 * @param Token[] $tokens
	 * @throws ParseException
	 */
	public function processTokens(string $expression, array &$tokens) : void{
		$this->deparenthesizeTokens($expression, $tokens);

		if(count($tokens) === 0){
			throw ParseException::emptyExpression($expression);
		}

		$this->groupFunctionCallTokens($tokens);
		$this->groupOperatorTokens($expression, $tokens);
		$this->transformFunctionCallTokens($expression, $tokens);

		if(count($tokens) > 1){
			$token = $tokens[1];
			while(is_array($token)){
				$token = $token[0];
			}
			throw ParseException::unexpectedToken($expression, $token);
		}
	}

	/**
	 * Transforms a given token tree in-place by removing parenthesis tokens
	 * {@see ParenthesisToken} by introducing nesting.
	 *
	 * This transforms [LP, NUM, OP, NUM, RP, OP, NUM] to [[NUM, OP, NUM], OP, NUM].
	 *
	 * @param string $expression
	 * @param Token[] $tokens
	 * @throws ParseException
	 */
	private function deparenthesizeTokens(string $expression, array &$tokens) : void{
		for($i = count($tokens) - 1; $i >= 0; --$i){
			$token = $tokens[$i];
			if(!($token instanceof ParenthesisToken)){
				continue;
			}

			if($token->getParenthesisMark() !== ParenthesisToken::MARK_OPENING){
				continue;
			}

			$j = $i + 1;
			$group = [];
			while(true){
				$member_token = $tokens[$j] ?? throw ParseException::noClosingParenthesis($expression, $token->getPos());
				if(!($member_token instanceof ParenthesisToken)){
					$group[] = $member_token;
					++$j;
					continue;
				}
				if($member_token->getParenthesisType() !== $token->getParenthesisType()){
					throw ParseException::unexpectedParenthesisType($expression, $member_token->getPos());
				}
				if($member_token->getParenthesisMark() !== ParenthesisToken::MARK_CLOSING){
					throw ParseException::noClosingParenthesis($expression, $token->getPos());
				}
				break;
			}

			array_splice($tokens, $i, 1 + ($j - $i), match(count($group)){
				0 => [],
				1 => $group,
				default => [$group]
			});
		}
		foreach($tokens as $token){
			if($token instanceof ParenthesisToken){
				assert($token->getParenthesisMark() === ParenthesisToken::MARK_CLOSING);
				throw ParseException::noOpeningParenthesis($expression, $token->getPos());
			}
		}
	}

	/**
	 * Transforms a given token tree in-place by grouping operator token instances
	 * together with their operand(s).
	 *
	 * @param string $expression
	 * @param Token[]|Token[][] $tokens
	 * @throws ParseException
	 */
	private function groupOperatorTokens(string $expression, array &$tokens) : void{
		foreach(Util::traverseNestedArray($tokens) as &$entry){
			$prioritize = [];
			do{
				foreach($this->operator_manager->getByPrecedence() as $list){
					foreach($list->getAssignment()->traverse($list, $entry) as $state){
						$index = $state->index;
						$token = $state->value;

						if(count($prioritize) > 0){
							if($prioritize[count($prioritize) - 1] !== spl_object_id($token)){
								continue;
							}
							array_pop($prioritize);
						}

						[$begin, $replacement] = match(true){
							$token instanceof BinaryOperatorToken => [$index - 1, [
								$entry[$index - 1] ?? throw ParseException::noBinaryOperandLeft($expression, $token->getPos()),
								$token,
								$entry[$index + 1] ?? throw ParseException::noBinaryOperandRight($expression, $token->getPos())
							]],
							$token instanceof UnaryOperatorToken => [$index, [
								$token,
								$entry[$index + 1] ?? throw ParseException::noUnaryOperand($expression, $token->getPos())
							]]
						};

						$operator_tokens = array_filter($replacement, static fn(Token|array $sub_token) : bool => $sub_token !== $token && (
							$sub_token instanceof BinaryOperatorToken ||
							$sub_token instanceof UnaryOperatorToken
						));
						if(count($operator_tokens) > 0){
							// discard replacement when an ungrouped operator is encountered
							// this ensures x ** -y does not result in [[x, **, -], y]
							$prioritize[] = spl_object_id($token);
							foreach($operator_tokens as $operator_token){
								$prioritize[] = spl_object_id($operator_token);
							}
							break;
						}

						$state->splice($begin, count($replacement), [$replacement]);
					}
				}
			}while(count($prioritize) > 0);
		}
	}

	/**
	 * Transforms a given token tree in-place by grouping all function calls with
	 * their argument list.
	 *
	 * @param Token[]|Token[][] $token_tree
	 */
	private function groupFunctionCallTokens(array &$token_tree) : void{
		foreach(Util::traverseNestedArray($token_tree) as &$entry){
			for($i = count($entry) - 1; $i >= 0; --$i){
				$token = $entry[$i];
				if($token instanceof FunctionCallToken){
					if(isset($entry[$i + 1]) && $token->getArgumentCount() > 0){
						array_splice($entry, $i, 2, [[$token, is_array($entry[$i + 1]) ? $entry[$i + 1] : [$entry[$i + 1]]]]);
					}else{
						$entry[$i] = [$token];
					}
				}
			}
		}
	}

	/**
	 * Transforms a given token tree in-place by resolving optional function parameters
	 * by their default values.
	 *
	 * @param string $expression
	 * @param Token[]|Token[][] $token_tree
	 * @throws ParseException
	 */
	private function transformFunctionCallTokens(string $expression, array &$token_tree) : void{
		foreach(Util::traverseNestedArray($token_tree) as &$entry){
			for($i = count($entry) - 1; $i >= 0; --$i){
				$token = $entry[$i];
				if(!($token instanceof FunctionCallToken)){
					continue;
				}

				try{
					$function = $this->function_registry->get($token->getFunction());
				}catch(InvalidArgumentException $e){
					throw ParseException::unresolvableFcallGeneric($expression, $token->getPos(), $e->getMessage(), $e);
				}

				$args_c = $token->getArgumentCount();

				$param_tokens = $entry[$i + 1] ?? [];
				assert(is_array($param_tokens));

				if(isset($param_tokens[0]) && $param_tokens[0] instanceof FunctionCallArgumentSeparatorToken){
					array_unshift($param_tokens, null);
				}

				$last = array_key_last($param_tokens);
				if($last !== null && $param_tokens[$last] instanceof FunctionCallArgumentSeparatorToken){
					$param_tokens[] = null;
				}

				for($j = count($param_tokens) - 1; $j >= 1; --$j){
					if($param_tokens[$j] instanceof FunctionCallArgumentSeparatorToken && $param_tokens[$j - 1] instanceof FunctionCallArgumentSeparatorToken){
						array_splice($param_tokens, $j - 1, 2, [$param_tokens[$j - 1], null, $param_tokens[$j]]);
					}
				}

				$params = [];
				for($j = 0, $max = count($param_tokens); $j < $max; ++$j){
					$param_token = $param_tokens[$j];
					if($j % 2 === 0 ? $param_token instanceof FunctionCallArgumentSeparatorToken : !($param_token instanceof FunctionCallArgumentSeparatorToken)){
						assert($param_token !== null);
						throw ParseException::unexpectedToken($expression, $param_token);
					}
					if($j % 2 === 0){
						$params[] = $param_token;
					}
				}

				for($j = count($params), $max = min(count($function->fallback_param_values) - ($function->variadic ? 1 : 0), $args_c); $j < $max; ++$j){
					$params[] = null;
				}

				$params_c = count($params);
				$l = 0;
				for($j = 0; $j < $params_c; ++$j){
					if($params[$j] === null){
						if(isset($function->fallback_param_values[$j])){
							$params[$j] = new NumericLiteralToken($token->getPos()->offset($l, $l), $function->fallback_param_values[$j]);
							++$l;
						}else{
							throw ParseException::unresolvableFcallNoDefaultParamValue($expression, $token, $j + 1);
						}
					}
				}

				if($params_c !== $args_c){
					throw new RuntimeException("Failed to parse complete list of arguments ({$params_c} !== {$args_c}) in function call at \"{$token->getPos()->in($expression)}\" ({$token->getPos()->getStart()}:{$token->getPos()->getEnd()}) in \"{$expression}\"");
				}

				if(!$function->variadic){
					$expected_c = count(array_filter($function->fallback_param_values, static fn(mixed $value) : bool => $value === null));
					if($params_c < $expected_c){
						throw ParseException::unresolvableFcallTooLessParams($expression, $token->getPos(), $expected_c, $params_c);
					}
					if($params_c > count($function->fallback_param_values)){
						throw ParseException::unresolvableFcallTooManyParams($expression, $token->getPos(), $function, $params_c);
					}
				}

				array_splice($entry, $i, $args_c > 0 ? 2 : 1, [[$token, ...$params]]);
			}
		}
	}

	/**
	 * Transforms a given token tree in-place to a flattened postfix representation.
	 *
	 * @param Token[]|Token[][] $postfix_token_tree
	 */
	public function convertTokenTreeToPostfixTokenTree(array &$postfix_token_tree) : void{
		$stack = [&$postfix_token_tree];
		while(($index = array_key_last($stack)) !== null){
			$entry = &$stack[$index];
			unset($stack[$index]);

			if($entry[0] instanceof FunctionCallToken){
				$entry[] = array_shift($entry);
			}

			$count = count($entry);
			if($count === 2 && $entry[0] instanceof UnaryOperatorToken){
				$entry = [&$entry[1], &$entry[0]];
			}
			if($count === 3 && $entry[1] instanceof BinaryOperatorToken){
				$entry = [&$entry[0], &$entry[2], $entry[1]];
			}

			for($i = 0; $i < $count; ++$i){
				if(is_array($entry[$i])){
					$stack[] = &$entry[$i];
				}
			}
		}

		Util::flattenArray($postfix_token_tree);
	}
}