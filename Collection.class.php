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
	
	public function add($conditions, $classname) {
		$keys = array_keys($conditions);
		$vals = array_values($conditions);
		
		$o = new $classname();
		for ($i=0; $i < count($keys); $i++) { 
			$o->$keys[$i] = $vals[$i];
		}
		$o->save();
		
		$this->objects[] = $o;
	}
	
	public function remove($conditions) {
		$keys = array_keys($conditions);
		$vals = array_values($conditions);
		
		$this->objects = array_filter($this->objects, function($el) use ($keys, $vals) {
			$keepit = false;
			for ($i=0; $i < count($keys); $i++) { 
				if ($el->$keys[$i] != $vals[$i]) $keepit = true;
			}
			
			if (!$keepit) $el->delete();
			
			return $keepit;
		});
	}
	
	public function exists($conditions) {
		$keys = array_keys($conditions);
		$vals = array_values($conditions);
		
		$leftovers = array_filter($this->objects, function($el) use($keys, $vals) {
			$keepit = true;
			for ($i=0; $i < count($keys); $i++) { 
				if ($el->$keys[$i] != $vals[$i]) $keepit = false;
			}
			
			return $keepit;
		});
		
		return count($leftovers) > 0;
	}
}

?>