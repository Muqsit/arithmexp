# arithmexp
An arithmetic expression solver

## Usage
```php
$operators = OperatorRegistry::default();

$expression = new ArithmeticExpression($operators, "4 - 3 + 5");
$value = $expression->solve(); // 6

$expression = new ArithmeticExpression($operators, "4 - x + y");
$value = $expression->solve(["x" => 3, "y" => 5]); // 6
$value = $expression->solve(["x" => 4, "y" => 5]); // 5
```
