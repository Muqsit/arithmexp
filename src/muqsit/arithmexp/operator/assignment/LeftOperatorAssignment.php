<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use muqsit\arithmexp\operator\OperatorList;
use muqsit\arithmexp\token\BinaryOperatorToken;
use Generator;
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
		$operators = $list->getBinary();
		$index = -1;
		$count = count($tokens);
		while(++$index < $count){
			$value = $tokens[$index];
			if($value instanceof BinaryOperatorToken && isset($operators[$value->getOperator()])){
				yield $index => $value;
				$index = -1;
				$count = count($tokens);
			}
		}
	}
}