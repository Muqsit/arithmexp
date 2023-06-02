<?php

declare(strict_types=1);

namespace muqsit\arithmexp\pattern\matcher;

use muqsit\arithmexp\expression\token\ExpressionToken;

final class InstanceOfPatternMatcher implements PatternMatcher{

	/**
	 * @param class-string<ExpressionToken> $class
	 */
	public function __construct(
		readonly private string $class
	){}

	public function matches(ExpressionToken|array $entry) : bool{
		if($entry instanceof ExpressionToken){
			return $entry instanceof $this->class;
		}
		foreach($entry as $token){
			if(!($token instanceof $this->class)){
				return false;
			}
		}
		return true;
	}
}