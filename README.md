# arithmexp
[![CI](https://github.com/Muqsit/arithmexp/actions/workflows/ci.yml/badge.svg)](https://github.com/Muqsit/arithmexp/actions/workflows/ci.yml)
[![Latest Release](https://img.shields.io/github/v/release/Muqsit/arithmexp)](https://github.com/Muqsit/arithmexp/releases/latest)

[`arithmexp`](https://github.com/Muqsit/arithmexp) is a powerful mathematical expression parser and evaluator library for PHP with support for [variable substitution (`x`, `v1`, etc.)](https://github.com/Muqsit/arithmexp#evaluating-a-mathematical-expression), [constant declaration](https://github.com/Muqsit/arithmexp/wiki), [deterministic and non-deterministic function registration](https://github.com/Muqsit/arithmexp/wiki), and more.

## Installation with composer
```
composer require muqsit/arithmexp
```

## Evaluating a mathematical expression
To evaluate a mathematical expression, a [`Parser`](https://github.com/Muqsit/arithmexp/blob/master/src/muqsit/arithmexp/Parser.php) instance must first be constructed.
The mathematical expression string must be passed in `Parser::parse()` to obtain a reusable [`Expression`](https://github.com/Muqsit/arithmexp/blob/master/src/muqsit/arithmexp/expression/Expression.php) instance.
The value of the mathematical expression can then be evaluated by invoking `Expression::evaluate()`.
```php
$parser = Parser::createDefault();
$expression = $parser->parse("2 + 3");
var_dump($expression->evaluate()); // int(5)
```

To substitute values of variables that occur within the supplied expression, an `array<string, int|float>` must be passed to `Expression::evaluate()`.
```php
$expression = $parser->parse("x + y");
var_dump($expression->evaluate(["x" => 2, "y" => 3])); // int(5)
var_dump($expression->evaluate(["x" => 1.5, "y" => 1.5])); // float(3)
```
The return value type of the evaluation is consistent with that of PHP's. As such, `int + int` returns an `int` value, whereas a `float + int|float` returns a `float` value. See documentation notes in the [**wiki**](https://github.com/Muqsit/arithmexp/wiki) for more details.
