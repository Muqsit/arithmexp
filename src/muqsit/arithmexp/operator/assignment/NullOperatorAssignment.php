<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use Generator;
use muqsit\arithmexp\operator\OperatorList;
use muqsit\arithmexp\token\UnaryOperatorToken;
use function count;

final class NullOperatorAssignment implements OperatorAssignment{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function getType() : int{
		return self::TYPE_NA;
	}

	public function traverse(OperatorList $list, string $expression, array &$tokens) : Generator{
		$state = new OperatorAssignmentTraverserState($tokens);
		for($i = count($tokens) - 1; $i >= 0; --$i){
			$token = $tokens[$i];
			if($token instanceof UnaryOperatorToken && isset($list->unary[$token->operator])){
				$state->index = $i;
				$state->value = $token;
				yield $state;
				if($state->changed){
					$i = count($tokens) - 1;
					$state->changed = false;
				}
			}
		}
	}
}