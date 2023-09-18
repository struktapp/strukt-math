<?php

use Strukt\Type\Number;
use Strukt\Monad;
use Strukt\Range;

if(!function_exists("number")){

	function number(mixed $number){

		return new Number($number);
	}
}

if(!function_exists("monos")){

	function monos(array $params){

		return new Monad($params);
	}
}

if(!function_exists("ranger")){

	function ranger($min = 0, $max = null){

		return new Range($min, $max);
	}
}