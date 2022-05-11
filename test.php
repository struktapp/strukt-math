<?php

$loader = require "vendor/autoload.php";
$loader->add("Strukt", __DIR__."/src");

$a = array(

	array(1,2,3),
	array(4,5,6),
	array(7,8,9)
);

$b = array(

	array(11,22,33),
	array(44,55,66),
	array(77,88,99)
);

$aa = Strukt\Matrix::create($a);
$bb = Strukt\Matrix::create($b);


echo $aa->multiply($bb);