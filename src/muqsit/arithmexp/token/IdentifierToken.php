<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\Position;

final class IdentifierToken extends SimpleToken{

	public function __construct(
		Position $position,
		private string $label
	){
		parent::__construct(TokenType::IDENTIFIER(), $position);
	}

	public function getLabel() : string{
		return $this->label;
	}

	public function repositioned(Position $position) : self{
		return new self($position, $this->label);
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