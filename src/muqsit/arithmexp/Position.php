<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

final class Position{

	/**
	 * @param Position[] $positions
	 * @return self
	 */
	public static function containing(array $positions) : self{
		$start = PHP_INT_MAX;
		$end = PHP_INT_MIN;
		foreach($positions as $position){
			if($position->start < $start){
				$start = $position->start;
			}
			if($position->end > $end){
				$end = $position->end;
			}
		}
		return new self($start, $end);
	}

	public function __construct(
		private int $start,
		private int $end,
	){}

	public function getStart() : int{
		return $this->start;
	}

	public function getEnd() : int{
		return $this->end;
	}

	public function length() : int{
		return $this->end - $this->start;
	}

	public function offset(int $start, int $end) : self{
		return new self($this->start + $start, $this->end + $end);
	}

	/**
	 * @return array<string, int>
	 */
	public function __debugInfo() : array{
		return ["start" => $this->start, "end" => $this->end];
	}
}