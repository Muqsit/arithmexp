<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use InvalidArgumentException;
use muqsit\arithmexp\expression\token\BooleanLiteralExpressionToken;
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
			$this->by_kind[($i++ << 5) | match(true){
				$token instanceof OpcodeExpressionToken => match($token->code){
					OpcodeToken::OP_BINARY_ADD => 0,
					OpcodeToken::OP_BINARY_DIV => 1,
					OpcodeToken::OP_BINARY_EQUAL => 2,
					OpcodeToken::OP_BINARY_EQUAL_NOT => 3,
					OpcodeToken::OP_BINARY_EXP => 4,
					OpcodeToken::OP_BINARY_GREATER_THAN => 5,
					OpcodeToken::OP_BINARY_GREATER_THAN_EQUAL_TO => 6,
					OpcodeToken::OP_BINARY_IDENTICAL => 7,
					OpcodeToken::OP_BINARY_IDENTICAL_NOT => 8,
					OpcodeToken::OP_BINARY_LESSER_THAN => 9,
					OpcodeToken::OP_BINARY_LESSER_THAN_EQUAL_TO => 10,
					OpcodeToken::OP_BINARY_MOD => 11,
					OpcodeToken::OP_BINARY_MUL => 12,
					OpcodeToken::OP_BINARY_SPACESHIP => 13,
					OpcodeToken::OP_BINARY_SUB => 14,
					OpcodeToken::OP_UNARY_NOT => 15,
					OpcodeToken::OP_UNARY_NVE => 16,
					OpcodeToken::OP_UNARY_PVE => 17
				},
				$token instanceof NumericLiteralExpressionToken,
				$token instanceof BooleanLiteralExpressionToken => 18,
				$token instanceof VariableExpressionToken => 19,
				$token instanceof FunctionCallExpressionToken => 20,
				default => 31
			}] = $token;
		}
	}

	public function evaluate(array $variable_values = []) : int|float|bool{
		$stack = [];
		$ptr = -1;
		foreach($this->by_kind as $index => $token){
			switch($index & 31){
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
					assert($token->code === OpcodeToken::OP_BINARY_EQUAL);
					$lvalue = $stack[$ptr - 1];
					$rvalue = $stack[$ptr];
					$stack[--$ptr] = $lvalue == $rvalue;
					break;
				case 3:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_EQUAL_NOT);
					$lvalue = $stack[$ptr - 1];
					$rvalue = $stack[$ptr];
					$stack[--$ptr] = $lvalue != $rvalue;
					break;
				case 4:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_EXP);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] **= $rvalue;
					break;
				case 5:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_GREATER_THAN);
					$lvalue = $stack[$ptr - 1];
					$rvalue = $stack[$ptr];
					$stack[--$ptr] = $lvalue > $rvalue;
					break;
				case 6:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_GREATER_THAN_EQUAL_TO);
					$lvalue = $stack[$ptr - 1];
					$rvalue = $stack[$ptr];
					$stack[--$ptr] = $lvalue >= $rvalue;
					break;
				case 7:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_IDENTICAL);
					$lvalue = $stack[$ptr - 1];
					$rvalue = $stack[$ptr];
					$stack[--$ptr] = $lvalue === $rvalue;
					break;
				case 8:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_IDENTICAL_NOT);
					$lvalue = $stack[$ptr - 1];
					$rvalue = $stack[$ptr];
					$stack[--$ptr] = $lvalue !== $rvalue;
					break;
				case 9:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_LESSER_THAN);
					$lvalue = $stack[$ptr - 1];
					$rvalue = $stack[$ptr];
					$stack[--$ptr] = $lvalue < $rvalue;
					break;
				case 10:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_LESSER_THAN_EQUAL_TO);
					$lvalue = $stack[$ptr - 1];
					$rvalue = $stack[$ptr];
					$stack[--$ptr] = $lvalue <= $rvalue;
					break;
				case 11:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_MOD);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] %= $rvalue;
					break;
				case 12:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_MUL);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] *= $rvalue;
					break;
				case 13:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_SPACESHIP);
					$lvalue = $stack[$ptr - 1];
					$rvalue = $stack[$ptr];
					$stack[--$ptr] = $lvalue <=> $rvalue;
					break;
				case 14:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_BINARY_SUB);
					$rvalue = $stack[$ptr];
					$stack[--$ptr] -= $rvalue;
					break;
				case 15:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_UNARY_NOT);
					$stack[$ptr] = !$stack[$ptr];
					break;
				case 16:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_UNARY_NVE);
					$stack[$ptr] = -$stack[$ptr];
					break;
				case 17:
					assert($token instanceof OpcodeExpressionToken);
					assert($token->code === OpcodeToken::OP_UNARY_PVE);
					$stack[$ptr] = +$stack[$ptr];
					break;
				case 18:
					assert($token instanceof BooleanLiteralExpressionToken || $token instanceof NumericLiteralExpressionToken);
					$stack[++$ptr] = $token->value;
					break;
				case 19:
					assert($token instanceof VariableExpressionToken);
					$stack[++$ptr] = $variable_values[$token->label] ?? throw new InvalidArgumentException("No value supplied for variable \"{$token->label}\" in \"{$this->expression}\"");;
					break;
				case 20:
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