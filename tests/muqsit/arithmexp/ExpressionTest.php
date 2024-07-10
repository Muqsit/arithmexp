<?php

declare(strict_types=1);

namespace muqsit\arithmexp;

use Closure;
use InvalidArgumentException;
use muqsit\arithmexp\function\FunctionFlags;
use muqsit\arithmexp\function\SimpleFunctionInfo;
use muqsit\arithmexp\operator\assignment\LeftOperatorAssignment;
use muqsit\arithmexp\operator\assignment\RightOperatorAssignment;
use muqsit\arithmexp\operator\binary\SimpleBinaryOperator;
use muqsit\arithmexp\operator\OperatorPrecedence;
use muqsit\arithmexp\operator\unary\SimpleUnaryOperator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_combine;
use function array_keys;
use function array_reverse;
use function array_values;
use function sort;

final class ExpressionTest extends TestCase{

	private Parser $parser;
	private Parser $uo_parser;

	protected function setUp() : void{
		$this->parser = Parser::createDefault();
		$this->uo_parser = Parser::createDefault();
	}

	public function testUndefinedVariable() : void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("No value supplied for variable \"x\" in \"pi * x\"");
		$this->parser->parse("pi * x")->evaluate();
	}

	public function testReturnTypeConsistency() : void{
		foreach(["x + y", "x - y", "x * y", "x / y"] as $expression_string){
			$expression = $this->parser->parse($expression_string);
			self::assertIsInt($expression->evaluate(["x" => 1, "y" => 1]));
			self::assertIsFloat($expression->evaluate(["x" => 1, "y" => 1.0]));
			self::assertIsFloat($expression->evaluate(["x" => 1.0, "y" => 1]));
			self::assertIsFloat($expression->evaluate(["x" => 1.0, "y" => 1.0]));
			self::assertIsInt($expression->evaluate(["x" => 1, "y" => true]));
			self::assertIsInt($expression->evaluate(["x" => true, "y" => true]));
		}
		self::assertIsBool($this->parser->parse("true || 1")->evaluate());
		self::assertIsBool($this->parser->parse("1 || true")->evaluate());
		self::assertIsBool($this->parser->parse("true || true")->evaluate());
		self::assertIsBool($this->parser->parse("1 || 1")->evaluate());
	}

	public function testOperatorAssociativity() : void{
		self::assertEquals(5 ** 4 ** 3 ** 2, $this->parser->parse("5 ** 4 ** 3 ** 2")->evaluate());
		self::assertEquals(5 ** (4 ** 3 ** 2), $this->parser->parse("5 ** (4 ** 3 ** 2)")->evaluate());
		self::assertEquals((5 ** 4 ** 3) ** 2, $this->parser->parse("(5 ** 4 ** 3) ** 2")->evaluate());
		self::assertEquals((5 ** 4) ** 3 ** 2, $this->parser->parse("(5 ** 4) ** 3 ** 2")->evaluate());
		self::assertEquals(4 ** 3 ** 2, $this->parser->parse("4 ** 3 ** 2")->evaluate());

		$variables = ["y" => 5, "w" => 4, "z" => 3, "x" => 2];
		$names = array_keys($variables);
		$val = array_values($variables);
		$names_sorted = $names;
		sort($names_sorted);
		foreach([$names, array_reverse($names), $names_sorted, array_reverse($names_sorted)] as $v){
			$vars = array_combine($v, $val);
			self::assertEquals($val[0] ** $val[1] ** $val[2] ** $val[3], $this->parser->parse("{$v[0]} ** {$v[1]} ** {$v[2]} ** {$v[3]}")->evaluate($vars));
			self::assertEquals($val[0] ** ($val[1] ** $val[2] ** $val[3]), $this->parser->parse("{$v[0]} ** ({$v[1]} ** {$v[2]} ** {$v[3]})")->evaluate($vars));
			self::assertEquals(($val[0] ** $val[1] ** $val[2]) ** $val[3], $this->parser->parse("({$v[0]} ** {$v[1]} ** {$v[2]}) ** {$v[3]}")->evaluate($vars));
			self::assertEquals(($val[0] ** $val[1]) ** $val[2] ** $val[3], $this->parser->parse("({$v[0]} ** {$v[1]}) ** {$v[2]} ** {$v[3]}")->evaluate($vars));
			self::assertEquals($val[1] ** $val[2] ** $val[3], $this->parser->parse("{$v[1]} ** {$v[2]} ** {$v[3]}")->evaluate($vars));
		}
	}

	public function testOperatorPrecedence() : void{
		self::assertEquals(7 - 3 - 2, $this->parser->parse("7 - 3 - 2")->evaluate());
		self::assertEquals(7 - 3 + 2, $this->parser->parse("7 - 3 + 2")->evaluate());
		self::assertEquals(7 + 3 - 2, $this->parser->parse("7 + 3 - 2")->evaluate());
		self::assertEquals(7 - 3 * 2, $this->parser->parse("7 - 3 * 2")->evaluate());
		self::assertEquals(7 * 3 - 2, $this->parser->parse("7 * 3 - 2")->evaluate());

		self::assertEquals(-3 ** 2, $this->parser->parse("-3 ** 2")->evaluate());
		self::assertEquals(-3 ** -2 ** 4, $this->parser->parse("-3 ** -2 ** 4")->evaluate());
		self::assertEquals(-(-(3 ** 2)), $this->parser->parse("--3 ** 2")->evaluate());
		self::assertEquals(3 ** -(-2), $this->parser->parse("3 ** --2")->evaluate());

		self::assertEquals(2 / -1 * 3 ** -3 / 4 * 5, $this->parser->parse("2 / -1 * 3 ** -3 / 4 * 5")->evaluate());
		self::assertEquals(2 / 3 + 4 * 5, $this->parser->parse("2 / 3 + 4 * 5")->evaluate());
		self::assertEquals(2 ** 3 ** 4, $this->parser->parse("2 ** 3 ** 4")->evaluate());
		self::assertEquals(2 ** 3 - 4 ** 5, $this->parser->parse("2 ** 3 - 4 ** 5")->evaluate());
	}

	public function testUnaryOperatorsInSequence() : void{
		self::assertEquals(1, $this->uo_parser->parse("--1")->evaluate());
		self::assertEquals(-1, $this->uo_parser->parse("-+1")->evaluate());
		self::assertEquals(1, $this->uo_parser->parse("-+-1")->evaluate());
		self::assertEquals(-1, $this->uo_parser->parse("---1")->evaluate());

		self::assertEquals(1, $this->parser->parse("--1")->evaluate());
		self::assertEquals(-1, $this->parser->parse("-+1")->evaluate());
		self::assertEquals(1, $this->parser->parse("-+-1")->evaluate());
		self::assertEquals(-1, $this->parser->parse("---1")->evaluate());
	}

	public function testFunctionCallOrder() : void{
		$capture = [];
		$fn = static function(int $id, int ...$_) use(&$capture) : int{
			$capture[] = $id;
			return 0;
		};

		$do_capture = static function(Closure $compute) use(&$capture) : array{
			$capture = [];
			$compute();
			[$result, $capture] = [$capture, []];
			return $result;
		};

		$this->parser->function_registry->registerFunction("fn", $fn);
		self::assertEquals(
			$do_capture(static fn() => $fn(1) + $fn(2, $fn(3), $fn(4)) ** $fn(5, $fn(6))),
			$do_capture(fn() => $this->parser->parse("fn(1) + fn(2, fn(3), fn(4)) ** fn(5, fn(6))")->evaluate())
		);
	}

	public function testDeterministicFunctionCall() : void{
		$disable_fcall = false;
		$fn = static function(int $value) use(&$disable_fcall) : int{
			if($disable_fcall){
				throw new RuntimeException("Attempted to call disabled function");
			}
			return $value;
		};

		$this->parser->function_registry->registerFunction("fn", $fn, FunctionFlags::DETERMINISTIC);
		$expression = $this->parser->parse("2 ** fn(2) + fn(fn(4))");
		$disable_fcall = true;
		self::assertEquals(2 ** 2 + 4, $expression->evaluate());
	}

	public function testVariadicFunctionCallWithNoArgs() : void{
		$expect = -1809580488;
		$fn = static fn(int ...$_) : int => $expect;
		$this->parser->function_registry->registerFunction("fn", $fn);
		self::assertEquals($expect, $this->parser->parse("fn()")->evaluate());
	}

	public function testVariadicFunctionCallWithVariableArgs() : void{
		$args = [-1272994651, -1912325829, 1481428815, 1337167590, -1613511579];
		$fn = static fn(int ...$numbers) : int => array_sum($numbers);
		$this->parser->function_registry->registerFunction("fn", $fn);
		self::assertEquals($fn(...$args), $this->parser->parse("fn(" . implode(", ", $args) . ")")->evaluate());
	}

	public function testFunctionCallWithUndefinedOptionalArgs() : void{
		$fn = static fn(float $value, int $precision = 0) : float => round($value, $precision);
		$this->parser->function_registry->registerFunction("fn", $fn);
		$expression = $this->parser->parse("39 * fn(40 * pi) / 47");
		self::assertEquals(39 * $fn(40 * M_PI) / 47, $expression->evaluate() + 1e-14);
	}

	public function testUnaryOperatorOnGroup() : void{
		$expression = $this->parser->parse("2 / -(3 * -6 / 8) + 4");
		self::assertEquals(2 / -(3 * -6 / 8) + 4, $expression->evaluate());
	}

	public function testNonstandardBinaryOperator() : void{
		$this->parser->operator_manager->binary_registry->register(new SimpleBinaryOperator(
			"..",
			"Random Range",
			0,
			RightOperatorAssignment::instance(),
			SimpleFunctionInfo::from(Closure::fromCallable("mt_rand"), 0)
		));

		$result = $this->parser->parse("27 / -(36..89 / 4.7) + 57")->evaluate();
		$range = [27 / -(36 / 4.7) + 57, 27 / -(89 / 4.7) + 57];
		self::assertGreaterThanOrEqual(min($range), $result);
		self::assertLessThanOrEqual(max($range), $result);
	}

	public function testNonstandardBinaryOperatorWithExistingSymbol() : void{
		$this->parser->operator_manager->binary_registry->register(new SimpleBinaryOperator(
			"//",
			"Integer Division",
			OperatorPrecedence::MULTIPLICATION_DIVISION_MODULO,
			LeftOperatorAssignment::instance(),
			SimpleFunctionInfo::from(Closure::fromCallable("intdiv"), 0)
		));

		$expression = $this->parser->parse("7 // 3");
		self::assertEquals(intdiv(7, 3), $expression->evaluate());
	}

	public function testNonstandardUnaryOperator() : void{
		$this->parser->operator_manager->unary_registry->register(new SimpleUnaryOperator(
			"±",
			"Modulus",
			OperatorPrecedence::UNARY_NEGATIVE_POSITIVE,
			SimpleFunctionInfo::from(Closure::fromCallable("abs"), 0)
		));

		$expression = $this->parser->parse("3 * ±(4 - 7) / 3.7");
		self::assertEquals(3 * abs(4 - 7) / 3.7, $expression->evaluate());
	}

	public function testNonstandardUnaryOperatorWithExistingSymbol() : void{
		$this->parser->operator_manager->unary_registry->register(new SimpleUnaryOperator(
			"--",
			"Decrement",
			OperatorPrecedence::UNARY_NEGATIVE_POSITIVE,
			SimpleFunctionInfo::from(static fn(int|float $x) : int|float => $x - 1, 0)
		));

		$expression = $this->parser->parse("7 * --3");
		self::assertEquals(7 * (3 - 1), $expression->evaluate());
	}

	public function testNonstandardConstant() : void{
		$c = 299_792_458;
		$this->parser->constant_registry->registerLabel("c", $c);
		$expression = $this->parser->parse("5.57 * c / -12.3 + 3 / c");
		self::assertEquals(5.57 * $c / -12.3 + 3 / $c, $expression->evaluate());
	}
}