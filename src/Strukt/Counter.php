<?php

namespace Strukt;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class Counter{

	private $counter;
	private static $counters = [];

	/**
	 * @param integer $start_at
	 */
	public function __construct(int $start_at = 0){

		$this->counter = number($start_at);
	}

	/**
	 * @param string $name
	 * @param integer $counter
	 * 
	 * @return static
	 */
	public static function create(string $name, int $counter = 0):static{

		if(array_key_exists($name, static::$counters))
			throw new \Exception(sprintf("Counter[%s] already exists!", $name));

		$newCounter = new self;
		static::$counters[$name] = $newCounter;

		return $newCounter;
	}

	/**
	 * @param string $name
	 * 
	 * @return static
	 */
	public static function get(string $name):static{

		return static::$counters[$name];		
	}

	/**
	 * @return void
	 */
	public function up():void{

		$this->counter = $this->counter->add(1);
	}

	/**
	 * @return void
	 */
	public function down(){

		$this->counter = $this->counter->subtract(1);
	}

	/**
	 * @param integer $value
	 * 
	 * @return integer
	 */
	public function equals(int $value):int{

		return $this->counter->equals($value);
	}

	/**
	 * @return integer
	 */
	public function yield(){

		return $this->counter->yield();
	}
}