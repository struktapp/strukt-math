<?php

use Strukt\Range;
use Strukt\Type\Number;

class RangerTest extends PHPUnit\Framework\TestCase{

	public function testRandomize(){

		$ranger = Range::create(0,1000);

		$nums = $ranger->random(3);
		foreach($nums as $num)
			$this->assertTrue(is_numeric($num));

		$this->assertTrue(count($nums) == 3);

		$start = new Number(100);
		$end = new Number(500);

		$nums = Range::create($start->yield(), $end->yield())->random(4);
		foreach($nums as $num)
			$this->assertTrue($start->lte($num) && $end->gte($num));
	}
}