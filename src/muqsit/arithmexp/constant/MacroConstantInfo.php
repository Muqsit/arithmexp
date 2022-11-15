<?php

declare(strict_types=1);

namespace muqsit\arithmexp\constant;

use Closure;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;
use muqsit\arithmexp\token\IdentifierToken;
use muqsit\arithmexp\token\Token;
use function array_splice;

final class MacroConstantInfo implements ConstantInfo{

	/**
	 * @param Closure(Parser $parser, string $expression, IdentifierToken $token) : list<Token> $resolver
	 */
	public function __construct(
		public Closure $resolver
	){}

	public function writeExpressionTokens(Parser $parser, string $expression, IdentifierToken $token, ExpressionTokenBuilderState $state) : void{
		array_splice($state->current_group, $state->current_index, 1, ($this->resolver)($parser, $expression, $token));
	}
}