<?php

namespace Strukt;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class Matrix{

	private $arr;

	/**
	 * @param array $arr
	 */
	public function __construct(array $arr){

		$this->arr = $arr;
	}

	/**
	 * @param array $arr
	 * 
	 * @return static
	 */
	public static function create(array $arr):static{

		return new self($arr);
	}

	/**
	 * @return static
	 */
	public function transpose():static{

		$b = $this->arr;

		return Matrix::create(array_map(function($idx) use($b){

			return array_column($b, $idx);

		}, array_keys($b)));
	}

	/**
	 * @param Matrix $b
	 * 
	 * @return static
	 */
	public function multiply(Matrix $b):static{

		if(count(reset($this->arr))!=count(array_column($b->yield(), 0)))
			throw new \Exception("Matrices dimensions incompatible!");

		$c = $b->transpose()->yield();

		return Matrix::create(array_map(function($aa) use($c){

			return array_map(function($cc) use($aa){

				return array_sum(array_map(function($aaa, $ccc){

					return $aaa*$ccc;

				}, $aa, $cc));

			}, $c);

		}, $this->arr));
	}

	/**
	 * @return array
	 */
	public function yield():array{

		return $this->arr;
	}

	/**
	 * @param string $dimensions
	 * @param integer $sequence
	 * 
	 * @return static
	 */
	public static function random(string $dimensions = "3x3", int $sequence = 10):static{

		if(!preg_match("/[0-9]+x[0-9]+/", $dimensions))
			throw new \Exception("Invalid matrix dimensions!");

		list($len, $wid) = explode("x", strtolower($dimensions));

		$result = array_map(fn()=>ranger(1, $sequence)->random($len), range(1, $wid));

		return \Strukt\Matrix::create($result);
	}

	public function __toString(){

		return implode("\n", array_map(function($y){

			return sprintf("[%s]", implode(",", $y));

		}, $this->arr));
	}
}