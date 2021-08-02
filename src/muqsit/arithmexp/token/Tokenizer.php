<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\operator\OperatorRegistry;

final class Tokenizer{

	private OperatorRegistry $operator_registry;
	private string $code;

	public function __construct(OperatorRegistry $operator_registry, string $code){
		$this->operator_registry = $operator_registry;
		$this->code = $code;
	}

	public function getCode() : string{
		return $this->code;
	}

	/**
	 * @param int $token_type
	 * @param string[] $characters
	 * @return Token[]
	 */
	private function tokenizeAnyCombinationOfChars(int $token_type, array $characters) : array{
		$token = null;
		$tokens = [];
		$length = strlen($this->code);
		for($i = 0; $i < $length; ++$i){
			$char = $this->code[$i];
			if(in_array($char, $characters, true)){
				$token ??= new Token($token_type, "", $i, $i - 1);
				$token->text .= $char;
				++$token->end_pos;
				if($i !== $length - 1){
					continue;
				}
			}
			if($token !== null){
				$tokens[] = $token;
				$token = null;
			}
		}
		return $tokens;
	}

	/**
	 * @param int $token_type
	 * @param string $string
	 * @return Token[]
	 */
	private function tokenizeExactMatch(int $token_type, string $string) : array{
		$tokens = [];
		$offset = 0;
		$length = strlen($string);
		while(($pos = strpos($this->code, $string, $offset)) !== false){
			$tokens[] = new Token($token_type, $string, $pos, $pos + $length - 1);
			$offset = $pos + $length;
		}
		return $tokens;
	}

	/**
	 * @return Token[]
	 */
	private function tokenizeNumbers() : array{
		$numbers = ["."];
		for($i = 0; $i < 10; ++$i){
			$numbers[] = (string) $i;
		}
		return $this->tokenizeAnyCombinationOfChars(TokenType::NUMBER, $numbers);
	}

	/**
	 * @return Token[]
	 */
	private function tokenizeOperators() : array{
		$matches = [];
		foreach($this->operator_registry->getCharacters() as $character){
			array_push($matches, ...$this->tokenizeExactMatch(TokenType::OPERATOR, $character));
		}
		return $matches;
	}

	/**
	 * @return Token[]
	 */
	private function tokenizeConstants() : array{
		$anywhere_characters = ["_"];
		for($i = "a"; $i <= "z"; ++$i){
			$anywhere_characters[] = $i;
		}
		for($i = "A"; $i <= "Z"; ++$i){
			$anywhere_characters[] = $i;
		}
		return $this->tokenizeAnyCombinationOfChars(TokenType::CONSTANT, $anywhere_characters);
	}

	/**
	 * @return Token[]
	 */
	private function tokenizeRest() : array{
		return [
			...$this->tokenizeExactMatch(TokenType::BRACKET_OPEN, "("),
			...$this->tokenizeExactMatch(TokenType::BRACKET_CLOSE, ")"),
			...$this->tokenizeAnyCombinationOfChars(TokenType::WHITESPACE, ["\t", " ", "\r", "\n"])
		];
	}

	/**
	 * @param int $token_type
	 * @param Token[] $tokens
	 * @return Token[]
	 */
	private function fillEmptySpaces(int $token_type, array $tokens) : array{
		$indexed_tokens = [];
		foreach($tokens as $token){
			for($i = $token->start_pos; $i <= $token->end_pos; ++$i){
				$indexed_tokens[$i] = $token;
			}
		}

		$result = [];
		$length_code = strlen($this->code);
		for($offset = 0; $offset < $length_code; ++$offset){
			$length = 0;
			while(!isset($indexed_tokens[$offset + $length])){
				++$length;
			}
			if($length > 0){
				$result[] = new Token($token_type, substr($this->code, $offset, $length), $offset, $offset + $length);
				$offset += $length;
			}
		}

		return $result;
	}

	/**
	 * @return Token[]
	 */
	public function tokenize() : array{
		$tokens = [
			...$this->tokenizeConstants(),
			...$this->tokenizeNumbers(),
			...$this->tokenizeOperators(),
			...$this->tokenizeRest()
		];
		array_push($tokens, ...$this->fillEmptySpaces(TokenType::INVALID, $tokens));
		TokenUtil::sort($tokens);
		return $tokens;
	}
}