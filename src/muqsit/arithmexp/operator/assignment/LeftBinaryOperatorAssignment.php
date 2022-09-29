<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use Generator;
use muqsit\arithmexp\token\BinaryOperatorToken;
use function count;

final class LeftBinaryOperatorAssignment implements BinaryOperatorAssignment{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function getType() : int{
		return self::TYPE_LEFT;
	}

	public function traverse(array $operators, array &$tokens) : Generator{
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