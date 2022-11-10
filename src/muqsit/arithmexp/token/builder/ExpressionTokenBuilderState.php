<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token\builder;

use Generator;
use muqsit\arithmexp\expression\token\ExpressionToken;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\Util;
use RuntimeException;

final class ExpressionTokenBuilderState{

	public int $current_index;

	/** @var list<Token|ExpressionToken|list<Token|ExpressionToken>> */
	public array $current_group;

	/**
	 * @param Parser $parser
	 * @param string $expression
	 * @param list<Token|ExpressionToken|list<Token|ExpressionToken>> $tokens
	 */
	public function __construct(
		public Parser $parser,
		public string $expression,
		public array $tokens
	){}

	/**
	 * @return Generator<ExpressionToken>
	 */
	public function toExpressionTokens() : Generator{
		$tokens = $this->tokens;
		Util::flattenArray($tokens);
		foreach($tokens as $token){
			if(!($token instanceof ExpressionToken)){
				throw new RuntimeException("Expected " . ExpressionToken::class . ", got " . $token::class);
			}
			yield $token;
		}
	}
}