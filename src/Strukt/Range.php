<?php

namespace Strukt;

use Strukt\Type\Number;

class Range{

	private $lowlimit;
	private $uplimit;

	public function __construct($lowlimit = 0, $uplimit = null){

		if(!is_null($lowlimit))
			if(!is_numeric($lowlimit))
				throw new \Exception("Strukt\Range.construct | Expected low limit numeral!");

		if(!is_null($uplimit))
			if(!is_numeric($uplimit))
				throw new \Exception("Strukt\Range.construct | Expected upper limit numeral!");

		$this->lowlimit = $lowlimit;
		$this->uplimit = $uplimit;
	}

	public static function create($lowlimit = 0, $uplimit = null){

		return new self($lowlimit, $uplimit);
	}

	/**
	* Check if number is within limits
	* 
	* Should only apply to Strukt\Number[create, add, subtract, negate, reset, round] methods only
	*/
	public function valid($number){

		if(!$number instanceof Number){

			if(!is_numeric($number))
				throw new \Exception("Strukt\Range.valid | Numeral required!");

			$number = Number::create($number);
		}

		$is_lowbound = true;
		if(!is_null($this->lowlimit))
			$is_lowbound = $number->gte($this->lowlimit);

		$is_upbound = true;
		if(!is_null($this->uplimit))
			$is_upbound = $number->lte($this->uplimit);

		return $is_upbound && $is_lowbound; 
	}

	public function random(int $qty = 1){

		$min = $this->lowlimit;
		$max = $this->uplimit;

		$i=0;
		while($i<=$qty-1){

			if(!is_null($min) && !is_null($max))
				$numbers[] = rand($min, $max);
			else
				$numbers[] = rand();

			if($i==$qty)
				break;
			
			$i++;
		}

		return $numbers;
	}
}