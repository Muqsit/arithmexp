<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator\assignment;

use muqsit\arithmexp\operator\OperatorList;
use muqsit\arithmexp\token\UnaryOperatorToken;
use Generator;
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

	public function traverse(OperatorList $list, array &$tokens) : Generator{
		$operators = $list->getUnary();
		for($i = count($tokens) - 1; $i >= 0; --$i){
			$token = $tokens[$i];
			if($token instanceof UnaryOperatorToken && isset($operators[$token->getOperator()])){
				yield $i => $token;
				$i = count($tokens) - 1;
			}
		}
	}
}