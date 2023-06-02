<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use Generator;
use muqsit\arithmexp\operator\OperatorList;
use muqsit\arithmexp\token\BinaryOperatorToken;
use function count;

final class RightOperatorAssignment implements OperatorAssignment{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function getType() : int{
		return self::TYPE_RIGHT;
	}

	public function traverse(OperatorList $list, array &$tokens) : Generator{
		$state = new OperatorAssignmentTraverserState($tokens);
		$operators = $list->binary;
		$index = count($tokens);
		while(--$index >= 0){
			$value = $tokens[$index];
			if($value instanceof BinaryOperatorToken && isset($operators[$value->operator])){
				$state->index = $index;
				$state->value = $value;
				yield $state;
				if($state->changed){
					$index = count($tokens);
					$state->changed = false;
				}
			}
		}
	}
}