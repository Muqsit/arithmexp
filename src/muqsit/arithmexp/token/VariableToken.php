<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

final class VariableToken extends SimpleToken{

	public function __construct(
		int $start_pos,
		int $end_pos,
		private string $label
	){
		parent::__construct(TokenType::VARIABLE(), $start_pos, $end_pos);
	}

	public function getLabel() : string{
		return $this->label;
	}

	public function __debugInfo() : array{
		$info = parent::__debugInfo();
		$info["label"] = $this->label;
		return $info;
	}

	public function jsonSerialize() : string{
		return $this->label;
	}
}