<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\NumericLiteralToken;
use function rtrim;
use function str_contains;
use function strlen;

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
			if($char === "."){
				if($offset === $length - 1 || !ctype_digit($expression[$offset + 1])){
					break;
				}
				if(str_contains($value, ".")){
					$trimmed = rtrim($value, ".");
					$offset -= strlen($value) - strlen($trimmed);
					$value = $trimmed;
					break;
				}
			}elseif(!ctype_digit($char)){
				break;
			}

			$value .= $char;
			$offset++;
		}

		if($value !== ""){
			$numeric_value = str_contains($value, ".") ? (float) $value : (int) $value;
			yield new NumericLiteralToken(new Position($start, $offset), $numeric_value);
		}
	}

	public function transform(TokenBuilderState $state) : void{
	}
}