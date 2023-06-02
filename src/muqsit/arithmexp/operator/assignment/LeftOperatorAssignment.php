<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use Generator;
use muqsit\arithmexp\operator\OperatorList;
use muqsit\arithmexp\token\BinaryOperatorToken;
use function count;

final class LeftOperatorAssignment implements OperatorAssignment{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function getType() : int{
		return self::TYPE_LEFT;
	}

	public function traverse(OperatorList $list, array &$tokens) : Generator{
		$state = new OperatorAssignmentTraverserState($tokens);
		$operators = $list->binary;
		$index = -1;
		$count = count($tokens);
		while(++$index < $count){
			$value = $tokens[$index];
			if($value instanceof BinaryOperatorToken && isset($operators[$value->operator])){
				$state->index = $index;
				$state->value = $value;
				yield $state;
				if($state->changed){
					$index = -1;
					$count = count($tokens);
					$state->changed = false;
				}
			}
		}
	}
}