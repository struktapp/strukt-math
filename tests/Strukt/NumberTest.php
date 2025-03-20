<?php

use Strukt\Type\Number;

class NumberTest extends PHPUnit\Framework\TestCase{

	private $num;

	public function setUp():void{

		$this->num = new Number(1000);
	}

	public function testAddition(){

		$this->assertTrue($this->num->add(200)->equals(1200));
	}

	public function testSubtraction(){

		$this->assertTrue($this->num->subtract(100)->equals(900));
	}

	public function testMultiplication(){

		$this->assertTrue($this->num->times(2)->equals(2000));
	}

	public function testDivision(){

		$this->assertTrue($this->num->parts(2)->equals(500));
	}

	public function testModulus(){

		$this->assertTrue($this->num->mod(11)->equals(10));
	}

	public function testPowers(){

		$this->assertTrue($this->num->raise(10)->equals(1000000000000000000000000000000));
	}

	public function testAllocation(){

		$this->assertEquals($this->num->ratio(1,1), array(500,500));
		$this->assertEquals($this->num->ratio(1,3), array(250,750));
		$this->assertEquals($this->num->ratio(1,1,3), array(200,200,600));
	}

	public function testInequalities(){

		$this->assertTrue($this->num->gt(999));
		$this->assertFalse($this->num->gt(1000));
		$this->assertTrue($this->num->lt(1001));
		$this->assertFalse($this->num->lt(999));
		$this->assertTrue($this->num->gte(1000));
		$this->assertFalse($this->num->gte(1001));
		$this->assertTrue($this->num->gte(999));
		$this->assertFalse($this->num->lte(999));
		$this->assertTrue($this->num->lte(1000));
		$this->assertTrue($this->num->lte(1001));
	}

	public function testFormat(){

		$num = Number::create(1000000)->format();

		$this->assertEquals("1,000,000.00", $num);
	}

	public function testRound(){

		$num = Number::create(10.5167277)->round(2);

		$this->assertEquals($num->yield(), 10.52);
	}
}