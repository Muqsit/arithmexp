<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use JsonSerializable;
use muqsit\arithmexp\Position;

interface Token extends JsonSerializable{

	public function getType() : TokenType;

	public function getPos() : Position;

	public function repositioned(Position $position) : self;
}