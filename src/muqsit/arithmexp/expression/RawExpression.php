<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression;

use InvalidArgumentException;
use muqsit\arithmexp\expression\token\FunctionCallExpressionToken;
use muqsit\arithmexp\expression\token\NumericLiteralExpressionToken;
use muqsit\arithmexp\expression\token\OpcodeExpressionToken;
use muqsit\arithmexp\expression\token\VariableExpressionToken;
use muqsit\arithmexp\token\OpcodeToken;
use RuntimeException;
use function array_slice;

final class RawExpression implements Expression{
	use GenericExpressionTrait;

	public function evaluate(array $variable_values = []) : int|float{
		$stack = [];
		$ptr = -1;
		foreach($this->postfix_expression_tokens as $token){
			if($token instanceof OpcodeExpressionToken){
				$code = $token->code;
				$rvalue = $stack[$ptr];
				if($code === OpcodeToken::OP_BINARY_ADD){
					$stack[--$ptr] += $rvalue;
				}elseif($code === OpcodeToken::OP_BINARY_DIV){
					$stack[--$ptr] /= $rvalue;
				}elseif($code === OpcodeToken::OP_BINARY_EXP){
					$stack[--$ptr] **= $rvalue;
				}elseif($code === OpcodeToken::OP_BINARY_MOD){
					$stack[--$ptr] %= $rvalue;
				}elseif($code === OpcodeToken::OP_BINARY_MUL){
					$stack[--$ptr] *= $rvalue;
				}elseif($code === OpcodeToken::OP_BINARY_SUB){
					$stack[--$ptr] -= $rvalue;
				}elseif($code === OpcodeToken::OP_UNARY_NVE){
					$stack[$ptr] = -$rvalue;
				}elseif($code === OpcodeToken::OP_UNARY_PVE){
					$stack[$ptr] = +$rvalue;
				}else{
					throw new RuntimeException("Don't know how to evaluate opcode: {$code}");
				}
			}elseif($token instanceof NumericLiteralExpressionToken){
				$stack[++$ptr] = $token->value;
			}elseif($token instanceof VariableExpressionToken){
				$stack[++$ptr] = $variable_values[$token->label] ?? throw new InvalidArgumentException("No value supplied for variable \"{$token->label}\" in \"{$this->expression}\"");;
			}elseif($token instanceof FunctionCallExpressionToken){
				$ptr -= $token->argument_count - 1;
				$stack[$ptr] = ($token->function)(...array_slice($stack, $ptr, $token->argument_count));
			}else{
				throw new RuntimeException("Don't know how to evaluate " . $token::class);
			}
		}

		return $stack[$ptr] ?? throw new RuntimeException("Could not evaluate \"{$this->expression}\"");
	}
}