<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use muqsit\arithmexp\operator\OperatorList;
use muqsit\arithmexp\token\BinaryOperatorToken;
use Generator;
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
		$operators = $list->getBinary();
		$index = count($tokens);
		while(--$index >= 0){
			$value = $tokens[$index];
			if($value instanceof BinaryOperatorToken && isset($operators[$value->getOperator()])){
				yield $index => $value;
				$index = count($tokens);
			}
		}
	}
}