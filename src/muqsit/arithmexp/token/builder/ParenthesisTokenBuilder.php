<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\Position;
use muqsit\arithmexp\token\ParenthesisToken;

final class ParenthesisTokenBuilder implements TokenBuilder{

	/** @var array<string, array{ParenthesisToken::MARK_*, ParenthesisToken::TYPE_*}> */
	private array $symbols_to_mark_type = [];

	public function __construct(){
		foreach([ParenthesisToken::MARK_OPENING, ParenthesisToken::MARK_CLOSING] as $mark){
			foreach([ParenthesisToken::TYPE_ROUND] as $type){
				$this->symbols_to_mark_type[ParenthesisToken::symbolFrom($mark, $type)] = [$mark, $type];
			}
		}
	}

	public function build(TokenBuilderState $state) : Generator{
		$char = $state->expression[$state->offset];
		if(isset($this->symbols_to_mark_type[$char])){
			[$mark, $type] = $this->symbols_to_mark_type[$char];
			yield new ParenthesisToken(new Position($state->offset, $state->offset + 1), $mark, $type);
		}
	}

	public function transform(TokenBuilderState $state) : void{
	}
}