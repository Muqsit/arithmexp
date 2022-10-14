<?php

declare(strict_types=1);

namespace muqsit\arithmexp\pattern;

use Generator;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\pattern\matcher\PatternMatcher;
use muqsit\arithmexp\Util;

final class Pattern{

	/**
	 * @param PatternMatcher $matcher
	 * @param ExpressionToken[]|ExpressionToken[][] $tree
	 * @return Generator<ExpressionToken[]>
	 */
	public static function &findMatching(PatternMatcher $matcher, array &$tree) : Generator{
		foreach(Util::traverseNestedArray($tree) as &$entry){
			if($matcher->matches($entry)){
				yield $entry;
			}
		}
	}
}