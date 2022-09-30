<?php

declare(strict_types=1);

use muqsit\arithmexp\Parser;
use PHPUnit\Framework\TestCase;

final class ExpressionTest extends TestCase{

	private Parser $parser;

	private function getParser() : Parser{
		return $this->parser ??= Parser::createDefault();
	}

	public function testReturnTypeConsistency() : void{
		foreach(["x + y", "x - y", "x * y", "x / y"] as $expression_string){
			$expression = $this->getParser()->parse($expression_string);
			$this->assertIsInt($expression->evaluate(["x" => 1, "y" => 1]));
			$this->assertIsFloat($expression->evaluate(["x" => 1, "y" => 1.0]));
			$this->assertIsFloat($expression->evaluate(["x" => 1.0, "y" => 1]));
			$this->assertIsFloat($expression->evaluate(["x" => 1.0, "y" => 1.0]));
		}
	}

	public function testOperatorPrecedence() : void{
		$parser = $this->getParser();

		$this->assertEquals($parser->parse("7 - 3 - 2")->evaluate(), 7 - 3 - 2);
		$this->assertEquals($parser->parse("7 - 3 + 2")->evaluate(), 7 - 3 + 2);
		$this->assertEquals($parser->parse("7 + 3 - 2")->evaluate(), 7 + 3 - 2);
		$this->assertEquals($parser->parse("7 - 3 * 2")->evaluate(), 7 - 3 * 2);
		$this->assertEquals($parser->parse("7 * 3 - 2")->evaluate(), 7 * 3 - 2);

		$this->assertEquals($parser->parse("2 / -1 * 3 ** -3 / 4 * 5")->evaluate(), 2 / -1 * 3 ** -3 / 4 * 5);
		$this->assertEquals($parser->parse("2 / 3 + 4 * 5")->evaluate(), 2 / 3 + 4 * 5);
		$this->assertEquals($parser->parse("2 ** 3 ** 4")->evaluate(), 2 ** 3 ** 4);
		$this->assertEquals($parser->parse("2 ** 3 - 4 ** 5")->evaluate(), 2 ** 3 - 4 ** 5);
	}

	public function testFunctionCallOrder() : void{
		$capture = [];
		$fcall_order_test_fn = static function(int $id, int ...$_) use(&$capture) : int{
			$capture[] = $id;
			return 0;
		};

		$do_capture = static function(Closure $compute) use(&$capture) : array{
			$capture = [];
			$compute();
			[$result, $capture] = [$capture, []];
			return $result;
		};

		$parser = $this->getParser();
		$parser->getFunctionRegistry()->register("fcall_order_test_fn", $fcall_order_test_fn);
		$this->assertEquals(
			$do_capture(static fn() => $parser->parse("fcall_order_test_fn(1) + fcall_order_test_fn(2, fcall_order_test_fn(3), fcall_order_test_fn(4)) ** fcall_order_test_fn(5, fcall_order_test_fn(6))")->evaluate()),
			$do_capture(static fn() => $fcall_order_test_fn(1) + $fcall_order_test_fn(2, $fcall_order_test_fn(3), $fcall_order_test_fn(4)) ** $fcall_order_test_fn(5, $fcall_order_test_fn(6)))
		);
	}
}