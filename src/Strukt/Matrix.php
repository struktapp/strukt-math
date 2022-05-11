<?php

namespace Strukt;

class Matrix{

	private $arr;

	public function __construct(array $arr){

		$this->arr = $arr;
	}

	public static function create(array $arr){

		return new self($arr);
	}

	public function transpose(){

		$b = $this->arr;

		return Matrix::create(array_map(function($idx) use($b){

			return array_column($b, $idx);

		}, array_keys($b)));
	}

	public function multiply(Matrix $b){

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

	public function yield(){

		return $this->arr;
	}

	public function __toString(){

		return implode("\n", array_map(function($y){

			return sprintf("[%s]", implode(",", $y));

		}, $this->arr));
	}
}