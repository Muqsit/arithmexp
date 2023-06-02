<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use function substr;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

final class Position{

	/**
	 * @param list<Position> $positions
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
		readonly public int $start,
		readonly public int $end
	){}

	public function length() : int{
		return $this->end - $this->start;
	}

	public function offset(int $start, int $end) : self{
		return new self($this->start + $start, $this->end + $end);
	}

	public function in(string $string) : string{
		return substr($string, $this->start, $this->length());
	}

	/**
	 * @return array<string, int>
	 */
	public function __debugInfo() : array{
		return ["start" => $this->start, "end" => $this->end];
	}
}