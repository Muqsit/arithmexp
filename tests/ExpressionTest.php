<?php

declare(strict_types=1);

use muqsit\arithmexp\operator\binary\assignment\LeftBinaryOperatorAssignment;
use muqsit\arithmexp\operator\binary\assignment\RightBinaryOperatorAssignment;
use muqsit\arithmexp\operator\binary\BinaryOperatorPrecedence;
use muqsit\arithmexp\operator\binary\SimpleBinaryOperator;
use muqsit\arithmexp\operator\unary\SimpleUnaryOperator;
use muqsit\arithmexp\Parser;
use PHPUnit\Framework\TestCase;

final class ExpressionTest extends TestCase{

	private Parser $parser;

	protected function setUp() : void{
		$this->parser = Parser::createDefault();
	}

	public function testReturnTypeConsistency() : void{
		foreach(["x + y", "x - y", "x * y", "x / y"] as $expression_string){
			$expression = $this->parser->parse($expression_string);
			self::assertIsInt($expression->evaluate(["x" => 1, "y" => 1]));
			self::assertIsFloat($expression->evaluate(["x" => 1, "y" => 1.0]));
			self::assertIsFloat($expression->evaluate(["x" => 1.0, "y" => 1]));
			self::assertIsFloat($expression->evaluate(["x" => 1.0, "y" => 1.0]));
		}
	}

	public function testOperatorPrecedence() : void{
		self::assertEquals(7 - 3 - 2, $this->parser->parse("7 - 3 - 2")->evaluate());
		self::assertEquals(7 - 3 + 2, $this->parser->parse("7 - 3 + 2")->evaluate());
		self::assertEquals(7 + 3 - 2, $this->parser->parse("7 + 3 - 2")->evaluate());
		self::assertEquals(7 - 3 * 2, $this->parser->parse("7 - 3 * 2")->evaluate());
		self::assertEquals(7 * 3 - 2, $this->parser->parse("7 * 3 - 2")->evaluate());

		self::assertEquals(2 / -1 * 3 ** -3 / 4 * 5, $this->parser->parse("2 / -1 * 3 ** -3 / 4 * 5")->evaluate());
		self::assertEquals(2 / 3 + 4 * 5, $this->parser->parse("2 / 3 + 4 * 5")->evaluate());
		self::assertEquals(2 ** 3 ** 4, $this->parser->parse("2 ** 3 ** 4")->evaluate());
		self::assertEquals(2 ** 3 - 4 ** 5, $this->parser->parse("2 ** 3 - 4 ** 5")->evaluate());
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

		$this->parser->getFunctionRegistry()->register("fcall_order_test_fn", $fcall_order_test_fn);
		self::assertEquals(
			$do_capture(static fn() => $fcall_order_test_fn(1) + $fcall_order_test_fn(2, $fcall_order_test_fn(3), $fcall_order_test_fn(4)) ** $fcall_order_test_fn(5, $fcall_order_test_fn(6))),
			$do_capture(fn() => $this->parser->parse("fcall_order_test_fn(1) + fcall_order_test_fn(2, fcall_order_test_fn(3), fcall_order_test_fn(4)) ** fcall_order_test_fn(5, fcall_order_test_fn(6))")->evaluate())
		);
	}

	public function testDeterministicFunctionCall() : void{
		$disable_fcall = false;
		$deterministic_fn = static function(int $value) use(&$disable_fcall) : int{
			if($disable_fcall){
				throw new RuntimeException("Attempted to call disabled function");
			}
			return $value;
		};

		$this->parser->getFunctionRegistry()->register("deterministic_fn", $deterministic_fn, true);
		$expression = $this->parser->parse("2 ** deterministic_fn(2) + deterministic_fn(deterministic_fn(4))");
		$disable_fcall = true;
		self::assertEquals(2 ** 2 + 4, $expression->evaluate());
	}

	public function testVariadicFunctionCallWithNoArgs() : void{
		$expect = -1809580488;
		$variadic_fn = static fn(int ...$_) : int => $expect;
		$this->parser->getFunctionRegistry()->register("variadic_no_args_fn", $variadic_fn);
		self::assertEquals($expect, $this->parser->parse("variadic_no_args_fn()")->evaluate());
	}

	public function testVariadicFunctionCallWithVariableArgs() : void{
		$args = [-1272994651, -1912325829, 1481428815, 1337167590, -1613511579];
		$variadic_fn = static fn(int ...$numbers) : int => array_sum($numbers);
		$this->parser->getFunctionRegistry()->register("variadic_var_args_fn", $variadic_fn);
		self::assertEquals($variadic_fn(...$args), $this->parser->parse("variadic_var_args_fn(" . implode(", ", $args) . ")")->evaluate());
	}

	public function testFunctionCallWithUndefinedOptionalArgs() : void{
		$default_args_fn = static fn(float $value, int $precision = 0) : float => round($value, $precision);
		$this->parser->getFunctionRegistry()->register("default_args_fn", $default_args_fn);
		$expression = $this->parser->parse("39 * default_args_fn(40 * pi) / 47");
		self::assertEquals(39 * $default_args_fn(40 * M_PI) / 47, $expression->evaluate());
	}

	public function testUnaryOperatorOnGroup() : void{
		$expression = $this->parser->parse("2 / -(3 * -6 / 8) + 4");
		self::assertEquals(2 / -(3 * -6 / 8) + 4, $expression->evaluate());
	}

	public function testNonstandardBinaryOperator() : void{
		$this->parser->getBinaryOperatorRegistry()->register(new SimpleBinaryOperator(
			"..",
			"Random Range",
			0,
			RightBinaryOperatorAssignment::instance(),
			Closure::fromCallable("mt_rand"),
			false
		));

		$result = $this->parser->parse("27 / -(36..89 / 4.7) + 57")->evaluate();
		$range = [27 / -(36 / 4.7) + 57, 27 / -(89 / 4.7) + 57];
		self::assertGreaterThanOrEqual(min($range), $result);
		self::assertLessThanOrEqual(max($range), $result);
	}

	public function testNonstandardBinaryOperatorWithExistingSymbol() : void{
		$this->parser->getBinaryOperatorRegistry()->register(new SimpleBinaryOperator(
			"//",
			"Integer Division",
			BinaryOperatorPrecedence::MULTIPLICATION_DIVISION_MODULO,
			LeftBinaryOperatorAssignment::instance(),
			Closure::fromCallable("intdiv"),
			false
		));

		$expression = $this->parser->parse("7 // 3");
		self::assertEquals(intdiv(7, 3), $expression->evaluate());
	}

	public function testNonstandardUnaryOperator() : void{
		$this->parser->getUnaryOperatorRegistry()->register(new SimpleUnaryOperator(
			"±",
			"Modulus",
			Closure::fromCallable("abs")
		));

		$expression = $this->parser->parse("3 * ±(4 - 7) / 3.7");
		self::assertEquals(3 * abs(4 - 7) / 3.7, $expression->evaluate());
	}

	public function testNonstandardUnaryOperatorWithExistingSymbol() : void{
		$this->parser->getUnaryOperatorRegistry()->register(new SimpleUnaryOperator(
			"--",
			"Decrement",
			static fn(int|float $x) : int|float => $x - 1
		));

		$expression = $this->parser->parse("7 * --3");
		self::assertEquals(7 * (3 - 1), $expression->evaluate());
	}

	public function testNonstandardConstant() : void{
		$c = 299_792_458;
		$this->parser->getConstantRegistry()->register("c", $c);
		$expression = $this->parser->parse("5.57 * c / -12.3 + 3 / c");
		self::assertEquals(5.57 * $c / -12.3 + 3 / $c, $expression->evaluate());
	}
}