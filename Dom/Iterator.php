<?php
class ABC_Dom_Iterator implements Iterator
{
	private $_list;
	
	public function __construct($array)
	{
		if(is_array($array))
			$this->_list = $array;
	}
	
	public function current()
	{
		return current($this->_list);
	}
	
	public function key()
	{
		return key($this->_list);
	}
	
	public function next()
	{
		return next($this->_list);
	}
	
	public function rewind()
	{
		reset($this->_list);
	}
	
	public function valid()
	{
		return $this->current() !== false;
	}
}