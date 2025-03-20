<?php

use Strukt\Type\Number;
use Strukt\Monad;
use Strukt\Range;
use Strukt\Counter;
use Strukt\Matrix;

helper("math");

if(helper_add("number")){

	/**
	 * @param mixed $number
	 * 
	 * @return \Strukt\Type\Number
	 */
	function number(mixed $number):Number{

		return new Number($number);
	}
}

if(helper_add("monos")){

	/**
	 * @param array $params
	 * 
	 * @return \Strukt\Monad
	 */
	function monos(array $params):Monad{

		return new Monad($params);
	}
}

if(helper_add("ranger")){

	/**
	 * @param integer $min
	 * @param integer $max
	 * 
	 * @return \Strukt\Range
	 */
	function ranger(int $min = 0, ?int $max = null):Range{

		return new Range($min, $max);
	}
}

if(helper_add("counter")){

	/**
	 * @param integer $start_at
	 * @param integer $name
	 * 
	 * @return \Strukt\Counter
	 */
	function counter(int $start_at = 0, ?string $name = null):Counter{

		if(!is_null($name))
			return Counter::create($name, $start_at);

		return new Counter($start_at);
	}
}

if(helper_add("counters")){

	/**
	 * @param string $name
	 * 
	 * @return \Strukt\Counter
	 */
	function counters(string $name):Counter{

		return Counter::get($name);
	}
}

if(helper_add("matrix")){

	/**
	 * @param array $base
	 * 
	 * @return mixed
	 */
	function matrix(?array $base){

		return new class($base){

			private $base = null;

			/**
			 * @param array $base
			 */
			public function __construct(?array $base){

				if(!is_null($base))
					$this->base = Strukt\Matrix::create($base);
			}

			/**
			 * @param array $multiplier
			 * 
			 * @return \Strukt\Matrix|null
			 */
			public function multiply(array $multiplier):?Matrix{

				$multiplier = Strukt\Matrix::create($multiplier);

				if(!is_null($this->base))
					return $this->base->multiply($multiplier);

				return null;
			}

			/**
			 * @param string $dimensions
			 * @param integer $sequence
			 * 
			 * @return \Strukt\Matrix
			 */
			public function random(string $dimensions = "3x3", int $sequence = 10):Matrix{

				return Strukt\Matrix::random($dimensions, $sequence);
			}

			/**
			 * @return \Strukt\Matrix
			 */
			public function transpose():Matrix{

				return $this->base->transpose();
			}

			public function yield(){

				return $this->base;
			}
		};
	}
}