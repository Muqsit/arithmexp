<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\token\NumericLiteralToken;

final class NumericLiteralTokenBuilder implements TokenBuilder{

	public function __construct(){
	}

	public function build(TokenBuilderState $state) : Generator{
		$value = "";
		$offset = $state->offset;
		$start = $offset;
		$length = $state->length;
		$expression = $state->expression;
		while($offset < $length){
			$char = $expression[$offset];
			if(($char !== "." || ($offset > $start && $expression[$offset - 1] === ".")) && !ctype_digit($char)){
				break;
			}

			$value .= $char;
			$offset++;
		}

		if($value !== ""){
			$numeric_value = str_contains($value, ".") ? (float) $value : (int) $value;
			yield new NumericLiteralToken($start, $offset, $numeric_value);
		}
	}

	public function transform(TokenBuilderState $state) : void{
	}
}