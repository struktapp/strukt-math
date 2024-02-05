Strukt Math
===

## Number (Value Objects)

```php
$num = number(1000);
$num = $num->add(200);//1200
$num = $num->subtract(100);//1100
$num = $num->times(2);//2200 multiplication
$num = $num->parts(4);//550 division
$rem = $num->mod(9);//1 modulus
$num = $num->raise(2);//302500 power
list($num1, $num2) = $num->ratio(1,1);//151250,151250
list($num1, $num2) = $num->ratio(1,3);//75625,226875
list($num1, $num2, $num3) = $num->ratio(1,1,3);//60500,60500,181500
$num->gt(302499);//true; greaterthan
$num->gte(302500);//true greaterthanorequals
$num->lt(302499);//false lessthan
$num->lte(302501);//true lessthanorequals
$num->negate()->equals(-302500)  
$num->yield();//return native number
$num->reset();//0
Number::create(1000000)->format();//1,000,000.00
Number::create(20.5111111)->round(2);//20.51
Number::random(4, 10, 20); //return 4 random numbers between 10 and 20
Number::create(10.1)->type();//double
echo $num;//return native number
```

## Matrix

```php
// $a = array(array(1,2,3),array(4,5,6),array(7,8,9));
$a = array(

    array(1,2,3),
    array(4,5,6),
    array(7,8,9)
);

// $b = array(array(11,22,33),array(44,55,66),array(77,88,99));
$b = array(

    array(11,22,33),
    array(44,55,66),
    array(77,88,99)
);


$c = (string)matrix($a)->multiply($b);
/** Result
[330,396,462]
[726,891,1056]
[1122,1386,165]
**/

$c = matrix($a)->multiply($b)->yield();
/** Result
array(
    array(330,396,462),
    array(726,891,1056),
    array(1122,1386,1650)
)
*/
```

## Monad

```php
// Linear Equation y = mx + c

$params = array("c"=>12, "m"=>3, "x"=>2)

$y = monos($params)->next(fn($m, $x)=>$m * $x)->next(fn($mx, $c)=>$mx + $c)->next(fn($r)=>$r);

echo $y->yield();
```