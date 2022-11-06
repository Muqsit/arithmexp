<?php

declare(strict_types=1);

namespace muqsit\arithmexp\function;

use Closure;
use InvalidArgumentException;
use muqsit\arithmexp\Parser;
use muqsit\arithmexp\token\builder\ExpressionTokenBuilderState;
use muqsit\arithmexp\token\FunctionCallToken;
use muqsit\arithmexp\token\Token;
use muqsit\arithmexp\Util;
use function array_slice;
use function array_splice;
use function count;

final class MacroFunctionInfo implements FunctionInfo{

	/**
	 * @param FunctionInfo $inner
	 * @param Closure(Parser $parser, string $expression, FunctionCallToken $token, Token[]|Token[][] $args) : (Token[]|null) $resolver
	 */
	public function __construct(
		public FunctionInfo $inner,
		public Closure $resolver
	){}

	public function getClosure() : Closure{
		return $this->inner->getClosure();
	}

	public function getFallbackParamValues() : array{
		return $this->inner->getFallbackParamValues();
	}

	public function isVariadic() : bool{
		return $this->inner->isVariadic();
	}

	public function getFlags() : int{
		return $this->inner->getFlags();
	}

	public function writeExpressionTokens(Parser $parser, string $expression, FunctionCallToken $token, ExpressionTokenBuilderState $state) : void{
		$args_c = $token->getArgumentCount();
		/** @var Token[]|Token[][] $args */
		$args = array_slice($state->current_group, $state->current_index - $args_c, $args_c);
		$result = ($this->resolver)($parser, $expression, $token, $args);
		if($result === null){
			$this->inner->writeExpressionTokens($parser, $expression, $token, $state);
		}else{
			if(count($result) === 0){
				throw new InvalidArgumentException("Macro must return a list of at least one element");
			}

			Util::flattenArray($args);
			Util::flattenArray($result);
			array_splice($state->current_group, $state->current_index - $args_c, $args_c + 1, $result);
			$state->current_index += count($result) - $args_c;
		}
	}
}