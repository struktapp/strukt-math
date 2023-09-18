<?php

namespace Strukt\Type;

use Strukt\Contract\ValueObject;

class Number extends ValueObject{

	public function __construct($number = 0){

		if(!is_numeric($number))
			throw new \Exception("Strukt\Type\Number.construct | Expected numeral!");
			
		$this->val = $number;
	}

	public static function create($number = 0){

		return new self($number);
	}

	public function add($number){

		$number = Number::eject($number);
	
		return new Number($this->val + $number);
	}

	public function subtract($number){

		$number = Number::objectify($number);

		$number = $number->negate();

		return $this->add($number);
	}

	public function round(int $precison = 0, int $mode = PHP_ROUND_HALF_UP){

		$number = round($this->val, $precison, $mode);

		return new Number($number);
	}

	public function negate(){

		return new Number(-1*$this->val);
	}

	public function reset(){
			
		$this->val = 0;
	}

	private static function objectify($number){

		if(!Number::valid($number))
			$number = new Number($number);

		return $number;
	}

	private static function eject($number){

		if(Number::valid($number))
			$number = $number->yield();

		return $number;
	}

	private static function valid($number){

		return $number instanceof Number;
	}

	/**
	* @var int 	  $precision Decimal Digits
	* @var string $dec_sep	 Decimal Separator
	* @var string $thou_sep	 Thousands Separator 
	*/
	public function format(int $precison = 2,  string $thou_sep = ",", string $dec_sep = "."){

		return number_format($this->val, $precison, $dec_sep, $thou_sep);
	}

	public function times($number){

		$number = Number::eject($number);

		return new Number($number*$this->val);
	}

	public function parts($number){

		$number = Number::eject($number);

		return new Number($this->val/$number);
	}

	public function mod($number){

		$number = Number::eject($number);

		return new Number($this->val%$number);
	}

	public function raise($number){

		$number = Number::eject($number);

		return new Number(pow($this->val, $number));
	}

	public function ratio(){

		$dividend = array_sum(func_get_args());

		$divisor = $this->parts($dividend);

		$parts = [];

		foreach (func_get_args() as $ratio){

			$ratio = new Number($ratio);

			$parts[] =  $ratio->times($divisor)->yield();
		}

		return $parts; 
	}

	public function equals($number){

		$number = Number::eject($number);

		return $this->val == $number;
	}

	public function gt($number){

		$number = Number::eject($number);

		return $this->val > $number;
	}

	public function lt($number){

		$number = Number::eject($number);

		return $this->val < $number;
	}

	public function lte($number){

		return $this->lt($number) || $this->equals($number);
	}

	public function gte($number){

		return $this->gt($number) || $this->equals($number);
	}

	public function type(){

		return gettype($this->val);
	}

	public function yield(){

		if(!is_numeric($this->val))
			throw new \Exception("Strukt\Type\Number.yield | NaN");

		return $this->val;
	}

	public function __toString(){

		return (string) $this->val;
	}
}