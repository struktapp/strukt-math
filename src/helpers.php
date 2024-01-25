<?php

use Strukt\Type\Number;
use Strukt\Monad;
use Strukt\Range;
use Strukt\Counter;

helper("math");

if(helper_add("number")){

	function number(mixed $number){

		return new Number($number);
	}
}

if(helper_add("monos")){

	function monos(array $params){

		return new Monad($params);
	}
}

if(helper_add("ranger")){

	function ranger($min = 0, $max = null){

		return new Range($min, $max);
	}
}

if(helper_add("counter")){

	function counter(int $start_at = 0, string $name = null){

		if(!is_null($name))
			return Counter::create($name, $start_at);

		return new Counter($start_at);
	}
}

if(helper_add("counters")){

	function counters(string $name){

		return Counter::get($name);
	}
}