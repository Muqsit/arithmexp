<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use Generator;

final class Util{

	/**
	 * @template T
	 * @param T[]|T[][] $array
	 * @return Generator<T[]>
	 */
	public static function &traverseNestedArray(array &$array) : Generator{
		$stack = [];

		$queue = [&$array];
		while(($index = array_key_first($queue)) !== null){
			$array = &$queue[$index];
			unset($queue[$index]);

			$stack[] = &$array;
			for($i = 0, $count = count($array); $i < $count; ++$i){
				if(is_array($array[$i])){
					$queue[] = &$array[$i];
				}
			}
		}

		$index = count($stack);
		while($index > 0){
			yield $stack[--$index];
		}
	}
}