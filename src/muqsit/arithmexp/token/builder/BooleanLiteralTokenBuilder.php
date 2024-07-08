<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\BooleanLiteralToken;
use muqsit\arithmexp\token\NumericLiteralToken;
use function rtrim;
use function str_contains;
use function strlen;
use function substr;

final class BooleanLiteralTokenBuilder implements TokenBuilder{

	private const VALUES = ["true" => true, "false" => false];
	private const BUFFER_LENGTH = 5; // max(len(keys(self::VALUES)))

	public function __construct(){
	}

	public function build(TokenBuilderState $state) : Generator{
		$buffer = "";
		$offset = $state->offset;
		$start = $offset;
		$length = $state->length;
		$expression = $state->expression;
		while($offset < $length){
			$char = $expression[$offset];
			$buffer = substr($buffer . $char, -self::BUFFER_LENGTH);
			if(isset(self::VALUES[$buffer])){
				$value = self::VALUES[$buffer];
				yield new BooleanLiteralToken(new Position($start, $offset), $value);
				break;
			}
			$offset++;
		}
	}

	public function transform(TokenBuilderState $state) : void{
	}
}