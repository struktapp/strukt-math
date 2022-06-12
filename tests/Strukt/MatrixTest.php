<?php

use Strukt\Matrix;

class MatrixTest extends PHPUnit\Framework\TestCase{

	public function testMultiply(){

		$a = array(

			array(1,2,3),
			array(4,5,6),
			array(7,8,9)
		);

		$b = array(

			array(11,22,33),
			array(44,55,66),
			array(77,88,99)
		);

		$aa = Matrix::create($a);
		$bb = Matrix::create($b);

		$ans = $aa->multiply($bb);

		$prod = array(

			array(330,396,462),
			array(726,891,1056),
			array(1122,1386,1650)
		);

		$this->assertEquals($ans->yield(), $prod);
	}
}