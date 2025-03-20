<?php

namespace Strukt;

use Strukt\Event;

/**
 * @author Moderator <pitsolu@gmail.com>
 */
class Monad{

	use Traits\Arr;

	private $result;
	private $params;
	private $params_assoc;

	/**
	 * @param array $params
	 */
	public function __construct(array $params){

		$this->params_assoc = $this->isMap($params);

		$this->params = $params;
	}

	/**
	 * @param array $params
	 */
	public static function create(array $params):Monad{

		return new self($params);
	}

	/**
	 * @param \Closure $step
	 * 
	 * @return void
	 */
	private function withAssocParams(\Closure $step):void{

		$evt = Event::create($step);

		if(!empty($this->result)){

			$evtParams = $evt->getParams();

			$paramKey = array_key_first($evtParams);

			$this->params[$paramKey] = $this->result;
		}

		$this->result = $evt->applyArgs($this->params)->exec();
	}

	/**
	 * @param \Closure $step
	 * 
	 * @return void
	 */
	private function withNoAssocParams(\Closure $step):void{

		$evt = Event::create($step);

		$evtParams = $evt->getParams();

		$this->result = $evt->applyArgs($this->params)->exec();

		$this->params = array_slice($this->params, count($evtParams));

		array_unshift($this->params, $this->result);		
	}

	/**
	 * @param \Closure $step
	 * 
	 * @return Monad
	 */
	public function next(\Closure $step):Monad{

		if($this->params_assoc)
			$this->withAssocParams($step);
		else
			$this->withNoAssocParams($step);			

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function yield():mixed{

		return $this->result;
	}
}