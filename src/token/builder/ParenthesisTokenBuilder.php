<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\token\LeftParenthesisToken;
use muqsit\arithmexp\token\RightParenthesisToken;

final class ParenthesisTokenBuilder implements TokenBuilder{

	public function __construct(){
	}

	public function build(TokenBuilderState $state) : Generator{
		$char = $state->expression[$state->offset];
		if($char === "("){
			yield new LeftParenthesisToken($state->offset, $state->offset + 1);
		}elseif($char === ")"){
			yield new RightParenthesisToken($state->offset, $state->offset + 1);
		}
	}
}