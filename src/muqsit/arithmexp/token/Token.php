<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class Token{

	public int $type;
	public string $text;
	public int $start_pos;
	public int $end_pos;

	public function __construct(int $type, string $text, int $start_pos, int $end_pos){
		$this->type = $type;
		$this->text = $text;
		$this->start_pos = $start_pos;
		$this->end_pos = $end_pos;
	}
}