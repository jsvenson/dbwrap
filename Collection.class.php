<?php

function __autoload($name) {
	include_once(Inflector::classify($name).'.class.php');
}

/**
* Collection
*/
class Collection {
	var $objects = array();
	
	function __construct($class, $args = array()) {
		$classname = Inflector::classify($class);
		$this->objects = $classname::find();
	}
	
	public function count($conditions = array()) {
		if (count($conditions) == 0) return count($this->objects);
		
		$filtered = $this->objects;
		foreach ($conditions as $key => $value) {
			$filtered = array_filter($filtered, function($el) use ($key, $value) {
				return strtolower($el->$key) == strtolower($value);
			});
		}
		
		return count($filtered);
	}
}

?>