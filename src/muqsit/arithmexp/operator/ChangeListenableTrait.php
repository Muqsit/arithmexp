<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

use Closure;
use function spl_object_id;

trait ChangeListenableTrait{

	/** @var array<int, Closure() : void> */
	private array $change_listeners = [];

	/**
	 * @param Closure() : void $listener
	 */
	final public function registerChangeListener(Closure $listener) : void{
		$this->change_listeners[spl_object_id($listener)] = $listener;
	}

	/**
	 * @param Closure() : void $listener
	 */
	final public function unregisterChangeListener(Closure $listener) : void{
		$this->change_listeners[spl_object_id($listener)] = $listener;
	}

	private function notifyChangeListener() : void{
		foreach($this->change_listeners as $listener){
			$listener();
		}
	}
}