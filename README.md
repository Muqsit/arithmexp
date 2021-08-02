# arithmexp
An arithmetic expression solver

## Usage
```php
$expression = new ArithmeticExpression("4 - 3 + 5");
$value = $expression->getValue(); // 6

$expression = new ArithmeticExpression("4 - x + y");
$value = $expression->getValue(["x" => 3, "y" => 5]); // 6
$value = $expression->getValue(["x" => 4, "y" => 5]); // 5
```
