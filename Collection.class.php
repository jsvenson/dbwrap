<?php

require_once('Inflector.class.php');

function __autoload($name) {
    include_once(Inflector::classify($name).'.class.php');
}

/**
* Collection
*/
class Collection implements Iterator {
    var $objects = array();
    private $classname = '';
    
    function __construct($class, $args = array()) {
        $this->classname = $classname = Inflector::classify($class);
        $conditions = array();
        foreach (array_keys($args) as $k) {
            $conditions[] = '`' . $k . '` = ?';
        }
        $this->objects = $classname::find('all', array(
            'conditions' => array_merge(array(implode(' and ', $conditions)), array_values($args))
        ));
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
    
    public function add($conditions) {
        $o = new $this->classname($conditions);
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
    
    # return object at index $idx
    public function index($idx) {
        return $idx >= $this->count() || $this->count() == 0? false : $this->objects[$idx];
    }
    
    # alias to index()
    public function eq($idx) {
        return $this->index($idx);
    }
    
    # return first object
    public function first() {
        return $this->count() == 0? false : $this->index(0);
    }
    
    # return last object
    public function last() {
        return $this->count() == 0? false : $this->index($this->count());
    }
    
    
    # Iterator functions
    public function rewind() {
        reset($this->objects);
    }
    
    public function current() {
        return current($this->objects);
    }
    
    public function key() {
        return key($this->objects);
    }
    
    public function next() {
        return next($this->objects);
    }
    
    public function valid() {
        $key = key($this->objects);
        return $key !== null && $key !== false;
    }
}

?>