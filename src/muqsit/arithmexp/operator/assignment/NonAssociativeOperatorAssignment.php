<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use Generator;
use muqsit\arithmexp\operator\OperatorList;
use muqsit\arithmexp\ParseException;
use muqsit\arithmexp\token\BinaryOperatorToken;
use function count;
use function is_array;

final class NonAssociativeOperatorAssignment implements OperatorAssignment{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function getType() : int{
		return self::TYPE_NON_ASSOCIATIVE;
	}

	private function getPairPrecedence(OperatorList $list, mixed $entry) : ?int{
		if(!is_array($entry) || count($entry) !== 3 || !($entry[1] instanceof BinaryOperatorToken)){
			return null;
		}
		if(!isset($list->binary[$entry[1]->operator])){
			return null;
		}
		return $list->binary[$entry[1]->operator]->getPrecedence();
	}

	public function traverse(OperatorList $list, string $expression, array &$tokens) : Generator{
		$state = new OperatorAssignmentTraverserState($tokens);
		$index = -1;
		$count = count($tokens);
		while(++$index < $count){
			$value = $tokens[$index];
			if($value instanceof BinaryOperatorToken && isset($list->binary[$value->operator])){
				$precedence = $list->binary[$value->operator]->getPrecedence();
				if($index > 0){
					$lvalue = $tokens[$index - 1];
					$lvalue_precedence = $this->getPairPrecedence($list, $lvalue);
					if($lvalue_precedence === $precedence){
						throw ParseException::undefinedOperatorAssociativity($expression, $value->getPos());
					}
				}
				if($index + 1 < $count){
					$rvalue = $tokens[$index + 1];
					$rvalue_precedence = $this->getPairPrecedence($list, $rvalue);
					if($rvalue_precedence === $precedence){
						throw ParseException::undefinedOperatorAssociativity($expression, $rvalue[1]->getPos());
					}
				}
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