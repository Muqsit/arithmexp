<?php

declare(strict_types=1);

namespace muqsit\arithmexp\token;

use muqsit\arithmexp\operator\OperatorRegistry;

final class Tokenizer{

	private const CHAR_READ = "\0";

	private OperatorRegistry $operator_registry;
	private string $code;
	private string $operating_code;

	public function __construct(OperatorRegistry $operator_registry, string $code){
		$this->operator_registry = $operator_registry;
		$this->code = $code;
	}

	public function getCode() : string{
		return $this->code;
	}

	/**
	 * @param int $token_type
	 * @param string[] $leading_characters
	 * @param string[] $characters
	 * @param string[] $trailing_characters
	 * @return Token[]
	 */
	private function tokenizeAnyCombinationOfChars(int $token_type, array $leading_characters, array $characters, array $trailing_characters) : array{
		$token = null;
		$tokens = [];
		$length = strlen($this->operating_code);
		for($i = 0; $i < $length; ++$i){
			$char = $this->operating_code[$i];
			if(
				($token === null && $i < $length - 1 && in_array($char, $leading_characters, true) && in_array($this->operating_code[$i + 1], $characters, true)) ||
				in_array($char, $characters, true) ||
				($token !== null && in_array($char, $trailing_characters, true))
			){
				$token ??= new Token($token_type, "", $i, $i - 1);
				$token->text .= $char;
				$this->operating_code[$i] = self::CHAR_READ;
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
		while(($pos = strpos($this->operating_code, $string, $offset)) !== false){
			for($i = 0; $i < $length; ++$i){
				$this->operating_code[$pos + $i] = self::CHAR_READ;
			}
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
		return $this->tokenizeAnyCombinationOfChars(TokenType::NUMBER, [], $numbers, []);
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
	private function tokenizeSymbols() : array{
		$tokens = [];
		$matches = [];
		preg_match_all('/[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*/m', $this->operating_code, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, 0);
		foreach($matches as [[$symbol, $offset]]){
			$length = strlen($symbol);
			for($i = 0; $i < $length; ++$i){
				$this->operating_code[$offset + $i] = self::CHAR_READ;
			}
			$tokens[] = new Token(TokenType::SYMBOL, $symbol, $offset, $offset + $length - 1);
		}
		return $tokens;
	}

	/**
	 * @return Token[]
	 */
	private function tokenizeRest() : array{
		return [
			...$this->tokenizeExactMatch(TokenType::BRACKET_OPEN, "("),
			...$this->tokenizeExactMatch(TokenType::BRACKET_CLOSE, ")"),
			...$this->tokenizeAnyCombinationOfChars(TokenType::WHITESPACE, [], ["\t", " ", "\r", "\n"], [])
		];
	}

	/**
	 * @return Token[]
	 */
	private function invalidateUnreadPortions() : array{
		$result = [];
		$length_code = strlen($this->operating_code);
		for($offset = 0; $offset < $length_code; ++$offset){
			$length = 0;
			while($this->operating_code[$offset + $length] !== self::CHAR_READ){
				++$length;
			}
			if($length > 0){
				$result[] = new Token(TokenType::INVALID, substr($this->operating_code, $offset, $length), $offset, $offset + $length);
				$offset += $length;
			}
		}
		return $result;
	}

	/**
	 * @return Token[]
	 */
	public function tokenize() : array{
		$this->operating_code = $this->code;
		$tokens = [
			...$this->tokenizeSymbols(),
			...$this->tokenizeNumbers(),
			...$this->tokenizeOperators(),
			...$this->tokenizeRest(),
			...$this->invalidateUnreadPortions()
		];
		TokenUtil::sort($tokens);
		return $tokens;
	}
}