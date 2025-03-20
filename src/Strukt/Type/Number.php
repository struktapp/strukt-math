<?php

namespace Strukt\Type;

use Strukt\Contract\ValueObject;

/**
 * Number value object
 * 
 * @author Moderator <pitsolu@gmail.com>
 */
class Number extends ValueObject{

	protected $val;

	/**
	 * @param int|float $number
	 */
	public function __construct(int|float $number = 0){

		if(!is_numeric($number))
			throw new \Exception("Strukt\Type\Number.construct | Expected numeral!");
			
		$this->val = $number;
	}

	/**
	 * @param int|float $number
	 * 
	 * @return Number
	 */
	#[\Override]
	public static function create($number = 0):Number{

		return new self($number);
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return Number
	 */
	public function add(Number|int|float $number):Number{

		$number = Number::eject($number);
	
		return new Number($this->val + $number);
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return Number
	 */
	public function subtract(Number|int|float $number):Number{

		$number = Number::objectify($number);

		$number = $number->negate();

		return $this->add($number);
	}

	/**
	 * @param integer $precision
	 * @param integer $mode 
	 * 		default: PHP_ROUND_HALF_UP - eg. 1.5 -> 2 | -1.5 -> -2
	 * 		PHP_ROUND_HALF_DOWN - eg. 1.5 -> 1 | -1.5 -> -1
	 * 		PHP_ROUND_HALF_EVEN - eg. 1.5 & 2.5 -> 2
	 * 		PHP_ROUND_HALF_ODD eg. 1.5 -> 1 & 2.5 -> 2
	 * 
	 * @return Number
	 */
	public function round(int $precision = 0, int $mode = PHP_ROUND_HALF_UP):Number{

		$number = round($this->val, $precision, $mode);

		return new Number($number);
	}

	/**
	 * @return Number
	 */
	public function negate():Number{

		return new Number(-1*$this->val);
	}

	/**
	 * @return void
	 */
	public function reset():void{
			
		$this->val = 0;
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return Number
	 */
	private static function objectify(Number|int|float $number):Number{

		if(!Number::valid($number))
			$number = new Number($number);

		return $number;
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return Number
	 */
	private static function eject(Number|int|float $number):Number|int|float{

		if(Number::valid($number))
			$number = $number->yield();

		return $number;
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return bool
	 */
	private static function valid(Number|int|float $number):bool{

		return $number instanceof Number;
	}

	/**
	* @param int $precision 	 Decimal Digits
	* @param string $dec_sep	 Decimal Separator
	* @param string $thou_sep	 Thousands Separator 
	* 
	* @return string
	*/
	public function format(int $precision = 2,  string $thou_sep = ",", string $dec_sep = "."):string{

		return number_format($this->val, $precision, $dec_sep, $thou_sep);
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return Number
	 */
	public function times(Number|int|float $number):Number{

		$number = Number::eject($number);

		return new Number($number*$this->val);
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return Number
	 */
	public function parts(Number|int|float $number):Number{

		$number = Number::eject($number);

		return new Number($this->val/$number);
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return Number
	 */
	public function mod(Number|int|float $number):Number{

		$number = Number::eject($number);

		return new Number($this->val%$number);
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return Number
	 */
	public function raise(Number|int|float $number):Number{

		$number = Number::eject($number);

		return new Number(pow($this->val, $number));
	}

	/**
	 * @param .....
	 * 
	 * @return array
	 */
	public function ratio():array{

		$dividend = array_sum(func_get_args());

		$divisor = $this->parts($dividend);

		$parts = [];

		foreach (func_get_args() as $ratio){

			$ratio = new Number($ratio);

			$parts[] =  $ratio->times($divisor)->yield();
		}

		return $parts; 
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return bool
	 */
	#[\Override]
	public function equals($number):bool{

		$number = Number::eject($number);

		return $this->val == $number;
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return bool
	 */
	public function gt(Number|int|float $number):bool{

		$number = Number::eject($number);

		return $this->val > $number;
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return bool
	 */
	public function lt(Number|int|float $number):bool{

		$number = Number::eject($number);

		return $this->val < $number;
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return bool
	 */
	public function lte(Number|int|float $number):bool{

		return $this->lt($number) || $this->equals($number);
	}

	/**
	 * @param Number|int|float $number
	 * 
	 * @return bool
	 */
	public function gte(Number|int|float $number):bool{

		return $this->gt($number) || $this->equals($number);
	}

	/**
	 * Internal gettype for \Strukt\Type\Number class
	 * 
	 * @return string
	 */
	public function type():string{

		return gettype($this->val);
	}

	/**
	 * @return int|float
	 */
	#[\Override]
	public function yield():int|float{

		if(!is_numeric($this->val))
			throw new \Exception("Strukt\Type\Number.yield | NaN");

		return $this->val;
	}

	public function __toString(){

		return (string) $this->val;
	}
}