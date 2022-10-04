<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use Generator;
use function array_splice;
use function count;
use function is_array;

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

	/**
	 * @param mixed[] $array
	 */
	public static function flattenArray(array &$array) : void{
		$stack = [&$array];
		while(($index = array_key_last($stack)) !== null){
			$entry = &$stack[$index];
			unset($stack[$index]);

			$count = count($entry);
			for($i = 0; $i < $count; ++$i){
				if(is_array($entry[$i])){
					array_splice($entry, $i, 1, $entry[$i]);
					$stack[] = &$entry;
					break;
				}
			}
		}
	}
}