<?php

use Strukt\Type\Number;
use Strukt\Monad;
use Strukt\Range;
use Strukt\Counter;

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

if(!function_exists("counter")){

	function counter(int $start_at = 0, string $name = null){

		if(!is_null($name))
			return Counter::create($name, $start_at);

		return new Counter($start_at);
	}

	function counters(string $name){

		return Counter::get($name);
	}
}