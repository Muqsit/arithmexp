<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use InvalidArgumentException;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\expression\token\OpcodeExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\token\OpcodeToken;
use RuntimeException;
use function array_slice;
use function assert;

/**
 * Although implementing a postfix expression evaluator is less demanding than what
 * is done here, this class intends to implement it in a way where evaluation has
 * the least overhead in PHP.
 */
final class RawExpression implements Expression{
	use GenericExpressionTrait{
		__construct as __parentConstruct;
	}

	/** @var array<int, ExpressionToken> */
	private array $by_kind = [];

	/**
	 * @param string $expression
	 * @param list<ExpressionToken> $postfix_expression_tokens
	 */
	public function __construct(string $expression, array $postfix_expression_tokens){
		$this->__parentConstruct($expression, $postfix_expression_tokens);

		$i = 0;
		foreach($postfix_expression_tokens as $token){
			$this->by_kind[($i++ << 4) | match(true){
				$token instanceof OpcodeExpressionToken => match($token->code){
					OpcodeToken::OP_BINARY_ADD => 0,
					OpcodeToken::OP_BINARY_DIV => 1,
					OpcodeToken::OP_BINARY_EXP => 2,
					OpcodeToken::OP_BINARY_MOD => 3,
					OpcodeToken::OP_BINARY_MUL => 4,
					OpcodeToken::OP_BINARY_SUB => 5,
					OpcodeToken::OP_UNARY_NVE => 6,
					OpcodeToken::OP_UNARY_PVE => 7
				},
				$token instanceof NumericLiteralExpressionToken => 8,
				$token instanceof VariableExpressionToken => 9,
				$token instanceof FunctionCallExpressionToken => 10,
				default => 15
			}] = $token;
		}
	}

	public function evaluate(array $variable_values = []) : int|float{
		$stack = [];
		$ptr = -1;
		foreach($this->by_kind  as $index => $token){
			switch($index & 15){
				case 0:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_ADD);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] += $rvalue;
					break;
				case 1:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_DIV);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] /= $rvalue;
					break;
				case 2:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_EXP);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] **= $rvalue;
					break;
				case 3:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_MOD);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] %= $rvalue;
					break;
				case 4:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_MUL);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] *= $rvalue;
					break;
				case 5:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_SUB);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] -= $rvalue;
					break;
				case 6:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_UNARY_NVE);
					$stack[$ptr] = -$stack[$ptr];
					break;
				case 7:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_UNARY_PVE);
					$stack[$ptr] = +$stack[$ptr];
					break;
				case 8:
					assert($token instanceof NumericLiteralExpressionToken);
					$stack[++$ptr] = $token->value;
					break;
				case 9:
					assert($token instanceof VariableExpressionToken);
					$stack[++$ptr] = $variable_values[$token->label] ?? throw new InvalidArgumentException("No value supplied for variable \"{$token->label}\" in \"{$this->expression}\"");;
					break;
				case 10:
					assert($token instanceof FunctionCallExpressionToken);
					$ptr -= $token->argument_count - 1;
					$stack[$ptr] = ($token->function)(...array_slice($stack, $ptr, $token->argument_count));
					break;
				default:
					throw new RuntimeException("Don't know how to evaluate " . $token::class);
			}
		}
		return $stack[$ptr] ?? throw new RuntimeException("Could not evaluate \"{$this->expression}\"");
	}
}