<?php

namespace Strukt;

class Counter{

	private $counter;
	private static $counters = [];

	public function __construct(int $start_at = 0){

		$this->counter = number($start_at);
	}

	public static function create(string $name, int $counter = 0){

		if(array_key_exists($name, static::$counters))
			throw new \Exception(sprintf("Counter[%s] already exists!", $name));

		$newCounter = new self;
		static::$counters[$name] = $newCounter;

		return $newCounter;
	}

	public static function get(string $name){

		return static::$counters[$name];		
	}

	public function up(){

		$this->counter = $this->counter->add(1);
	}

	public function down(){

		$this->counter = $this->counter->subtract(1);
	}

	public function equals(int $value){

		return $this->counter->equals($value);
	}

	public function yield(){

		return $this->counter->yield();
	}
}