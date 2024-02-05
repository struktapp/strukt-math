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

if(helper_add("matrix")){

	function matrix(array $base = null){

		return new class($base){

			private $base = null;

			public function __construct(array $base = null){

				if(!is_null($base))
					$this->base = Strukt\Matrix::create($base);
			}

			public function multiply(array $multiplier){

				$multiplier = Strukt\Matrix::create($multiplier);

				if(!is_null($this->base))
					return $this->base->multiply($multiplier);

				return null;
			}

			public function random(string $dimensions = "3x3", int $sequence = 10){

				return Strukt\Matrix::random($dimensions, $sequence);
			}

			public function transpose(){

				return $this->base->transpose();
			}

			public function yield(){

				return $this->base;
			}
		};
	}
}