<?php

declare(strict_types=1);

namespace muqsit\arithmexp\expression\token;

use muqsit\arithmexp\Position;
use Stringable;

interface ExpressionToken extends Stringable{

	public function getPos() : Position;

	public function isDeterministic() : bool;

	public function equals(self $other) : bool;
}