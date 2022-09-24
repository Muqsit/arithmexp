<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use JsonSerializable;

interface Token extends JsonSerializable{

	public function getType() : TokenType;

	public function getStartPos() : int;

	public function getEndPos() : int;
}