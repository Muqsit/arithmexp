<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\binary\assignment;

use Generator;
use muqsit\arithmexp\token\BinaryOperatorToken;
use function count;

final class RightBinaryOperatorAssignment implements BinaryOperatorAssignment{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function getType() : int{
		return self::TYPE_RIGHT;
	}

	public function traverse(array $operators, array &$tokens) : Generator{
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