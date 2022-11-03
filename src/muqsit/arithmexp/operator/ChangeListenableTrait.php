<?php

declare(strict_types=1);

namespace muqsit\arithmexp\operator;

use Closure;
use InvalidArgumentException;
use function spl_object_id;

trait ChangeListenableTrait{

	/** @var array<int, Closure(self) : void> */
	private array $change_handlers = [];

	/** @var array<int, Closure(self) : void> */
	private array $change_listeners = [];

	/**
	 * @param Closure(self) : void $handler
	 */
	final public function registerChangeHandler(Closure $handler) : void{
		$this->change_handlers[spl_object_id($handler)] = $handler;
	}

	/**
	 * @param Closure(self) : void $handler
	 */
	final public function unregisterChangeHandler(Closure $handler) : void{
		$this->change_handlers[spl_object_id($handler)] = $handler;
	}

	private function notifyChangeHandler() : void{
		foreach($this->change_handlers as $handler){
			/** @throws InvalidArgumentException */
			$handler($this);
		}
	}

	/**
	 * @param Closure(self) : void $listener
	 */
	final public function registerChangeListener(Closure $listener) : void{
		$this->change_listeners[spl_object_id($listener)] = $listener;
	}

	/**
	 * @param Closure(self) : void $listener
	 */
	final public function unregisterChangeListener(Closure $listener) : void{
		$this->change_listeners[spl_object_id($listener)] = $listener;
	}

	private function notifyChangeListener() : void{
		foreach($this->change_listeners as $listener){
			$listener($this);
		}
	}
}