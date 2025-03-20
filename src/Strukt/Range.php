<?php

namespace Strukt;

use Strukt\Type\Number;

/** 
 * @author Moderator <pitsolu@gmail.com>
 */
class Range{

	private $lowlimit;
	private $uplimit;

	/**
	 * @param integer $lowlimit
	 * @param integer $uplimit
	 */
	public function __construct(int $lowlimit = 0, ?int $uplimit = null){

		if(!is_null($lowlimit))
			if(!is_numeric($lowlimit))
				throw new \Exception("Strukt\Range.construct | Expected low limit numeral!");

		if(!is_null($uplimit))
			if(!is_numeric($uplimit))
				throw new \Exception("Strukt\Range.construct | Expected upper limit numeral!");

		$this->lowlimit = $lowlimit;
		$this->uplimit = $uplimit;
	}

	/**
	 * @param integer $lowlimit
	 * @param integer $uplimit
	 * 
	 * @return static
	 */
	public static function create(int $lowlimit = 0, ?int $uplimit = null):static{

		return new self($lowlimit, $uplimit);
	}

	/**
	* Check if number is within limits
	* 
	* Should only apply to Strukt\Number[create, add, subtract, negate, reset, round] methods only
	* 
	* @param Number|int $number
	* 
	* @return bool
	*/
	public function valid(Number|int $number):bool{

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

	/**
	 * @param integer $qty 
	 * 
	 * @return array
	 */
	public function random(int $qty = 1):array{

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