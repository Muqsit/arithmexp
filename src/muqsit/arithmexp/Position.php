<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

final class Position{

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