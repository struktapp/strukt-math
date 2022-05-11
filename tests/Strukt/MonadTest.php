<?php

use Strukt\Monad;

class MonadTest extends PHPUnit\Framework\TestCase{

	//y = mx+c
	public function linearEq($params){

		$y = Monad::create($params)
			->next(function($m, $x){

				$mx = $m * $x;

				return $mx;
			})
			->next(function($mx, $c){

				return $mx + $c;
			})
			->next(function($r){

				return $r;
			});

		return $y->yield();
	}

	public function testMathWithAssocExample(){

		$this->assertEquals($this->linearEq(array("c"=>12, "m"=>3, "x"=>2)), 18);
	}

	public function testMathWithNoAssocExample(){

		$this->assertEquals($this->linearEq(array(12, 3, 2)), 38);
	}
}