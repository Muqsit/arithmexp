<?php

declare(strict_types=1);

namespace muqsit\arithmexp\pattern;

use Generator;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\pattern\matcher\InstanceOfPatternMatcher;
use muqsit\arithmexp\pattern\matcher\NotPatternMatcher;
use muqsit\arithmexp\pattern\matcher\PatternMatcher;
use muqsit\arithmexp\Util;

final class Pattern{

	/**
	 * @param PatternMatcher $matcher
	 * @param ExpressionToken[]|ExpressionToken[][] $tree
	 * @return Generator<ExpressionToken[]>
	 */
	public static function &findMatching(PatternMatcher $matcher, array &$tree) : Generator{
		/** @var ExpressionToken[] $entry */
		foreach(Util::traverseNestedArray($tree) as &$entry){
			if($matcher->matches($entry)){
				yield $entry;
			}
		}
	}

	public static function not(PatternMatcher $matcher) : PatternMatcher{
		return new NotPatternMatcher($matcher);
	}

	/**
	 * @param class-string<ExpressionToken> $type
	 * @return PatternMatcher
	 */
	public static function instanceof(string $type) : PatternMatcher{
		return new InstanceOfPatternMatcher($type);
	}
}