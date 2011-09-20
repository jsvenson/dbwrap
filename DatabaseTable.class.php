<?php

require_once('Inflector.class.php');
require_once('dbConstants.class.php');

/**
* DatabaseTable
*/
abstract class DatabaseTable {
	var $_db         = null;
	var $_tablename  = '';
	
	var $id          = 0;
	var $created     = '';
	var $updated     = '';
	
	var $_types      = array(); # column types
	
	function __construct($id = 0) {
		$this->_types['id'] = 'i';
		$this->_types['created'] = 's';
		$this->_types['updated'] = 's';
		if ($id > 0) $this->load($id);
	}
	
	protected function openConnection() {
		$c = get_called_class();
		if ($this->_db == null) {
			$this->_db = new MySQLi(
				dbConstants::_dbserver,
				dbConstants::_dbuser,
				base64_decode(dbConstants::_dbpass),
				dbConstants::_dbname
			);
			if ($this->_db->connect_errno > 0) throw new Exception('Error connecting to database: '.$this->db->connect_error());
		}
	}
	
	protected function closeConnection() {
		if ($this->_db != null) {
			$this->_db->close();
			$this->_db = null;
		}
	}
	
	public function delete() {
		$this->openConnection();
		
		if (!($stmt = $this->_db->prepare('delete from '.$this->_tablename.' where id = ?')))
			throw new Exception('Problem preparing delete statement: '.$this->_db->error);
		
		if (!$stmt->bind_param('i', $this->id))
			throw new Exception('Problem binding parameter for delete: '.$stmt->error);
		
		if (!$stmt->execute())
			throw new Exception ('Problem deleting record: '.$stmt->error);
		
		$this->closeConnection();
		
		return true;
	}
	
	public function load($id = 0) {
		if ($id == 0) return false;
		
		$this->openConnection();
		
		$columns = '`'.implode('`,`', $this->columns()).'`';
		
		if (!($stmt = $this->_db->prepare('select '.$columns.' from '.$this->_tablename.' where id = ?')))
			throw new Exception('Problem preparing select statement: '.$this->_db->error);
		$stmt->bind_param('i', $id);
		
		if (!$stmt->execute()) throw new Exception('Problem loading record: '.$stmt->error);
		
		$params = array();
		$c = $this->columns();
		for ($i=0; $i < count($c); $i++) { 
			$params[] = &$this->$c[$i];
		}
		
		if ((call_user_func_array(array($stmt, 'bind_result'), $params)) === false)
			throw new Exception('No record for id '.htmlentities($id));
		
		if (!$stmt->fetch()) throw new Exception('No record for id '.htmlentities($id));
		
		$stmt->close();
		
		$this->closeConnection();
		
		return true;
	}
	
	public function save() {
		$this->openConnection();
		
		$ignore  = array_keys(get_class_vars('DatabaseTable'));
		$columns = array_filter($this->columns(), function($a) use ($ignore) {
			return !in_array($a, $ignore);
		});
		
		sort($columns); # reorder array indexes
		
		if ($this->id == 0) {
			$cols   = '`created`, `updated`, '.'`'.implode('`, `', $columns).'`';
			$values = 'now(), now()'.str_repeat(', ?', count($columns));
			
			if (!($stmt = $this->_db->prepare('insert into '.$this->_tablename.' ('.$cols.') values ('.$values.')')))
				throw new Exception('Problem preparing insert statement: '.$this->_db->error);
			
			$params = array();
			$types  = '';
			for ($i=0; $i < count($columns); $i++) { 
				$types .= $this->_types[$columns[$i]];
				$params[] = &$this->$columns[$i];
			}
		} else {
			$values = '';
			$types  = '';
			$params = array();
			
			for ($i=0; $i < count($columns); $i++) { 
				$types  .= $this->_types[$columns[$i]];
				$values .= ', `'.$columns[$i].'`=?';
				$params[] = &$this->$columns[$i];
			}
			
			$types   .= 'i';        # add final type for id
			$params[] = &$this->id; # add id to $params
			
			if (!$stmt = $this->_db->prepare('update '.$this->_tablename.' set `updated`=now()'.$values.' where `id`=?'))
				throw new Exception('Problem preparing update statement: '.$this->_db->error);
		}
		
		array_unshift($params, $types); # add types to start of $params
		
		if (!call_user_func_array(array($stmt, 'bind_param'), $params))
			throw new Exception('Problem binding parameters: '.$stmt->error);
		
		if (!$stmt->execute()) throw new Exception('Problem saving record: '.$stmt->error, $stmt->errno);
		
		$stmt->close();
		
		$this->load(($this->_db->insert_id > 0 ? $this->_db->insert_id : $this->id));
		
		$this->closeConnection();
		
		return true;
	}
	
	public function columns($flat = false) {
		$columns = array_filter(array_keys(get_class_vars(get_class($this))), function($el) {
			return substr($el, 0, 1) != '_';
		});
		
		sort($columns);
		
		return $flat ? '`'.implode('`,`', $this->columns()).'`' : $columns;
	}
	
	# supports find_by_ and find_all_by_ for any column
	# second argument of $args only supports order by clause
	public static function __callStatic($name, $args) {
		if (substr($name, 0, 11) != 'find_all_by' && substr($name, 0, 7) != 'find_by')
			throw new Exception ('Unknown method call: '.$name);
		
		$limit     = substr($name, 0, 7) == 'find_by' ? 1 : 0;
		$column    = substr($name, $limit==1?8:12);
		$col_types = array();
		
		$data_types = array(
			'bigint' => 'i', 'binary' => 'i', 'blob' => 'b', 'char' => 's', 'date' => 's', 'datetime' => 's', 'decimal' => 'd',
			'enum' => 's', 'int' => 'i', 'longblob' => 'b', 'mediumblob' => 'b', 'mediumtext' => 's', 'set' => 's',
			'smallint' => 'i', 'text' => 's', 'time' => 's', 'timestamp' => 's', 'tinyint' => 'i', 'varchar' => 's'
		);
		
		$mysqli = new MySQLi(
			dbConstants::_dbserver,
			dbConstants::_dbuser,
			base64_decode(dbConstants::_dbpass),
			dbConstants::_dbname
		);
		
		$tablename = Inflector::pluralize(get_called_class());
		
		$query = 'select column_name, data_type from information_schema.columns where table_schema=\''.dbConstants::_dbname.'\' and table_name=\''.$tablename.'\'';
		$stmt = $mysqli->prepare($query);
		$stmt->execute();
		$stmt->bind_result($column_name, $data_type);
		while ($stmt->fetch()) {
			$col_types[$column_name] = $data_types[$data_type];
		}
		$stmt->close();
		
		if (!isset($col_types[$column])) throw new Exception('Unknown column \''.$column.'\'');
		
		$col_keys = array_keys($col_types);
		
		$query = 'select `'.implode('`,`', $col_keys).'` from `'
			. $tablename .'` where `' . $mysqli->real_escape_string($column) . '` = ?';
		
		if (count($args) > 1) {
			$order = isset($args[1]['order']) ? $mysqli->real_escape_string($args[1]['order']) : 'created asc';
			$query .= ' order by '.$order;
		}
		
		if ($limit > 0) $query .= ' limit 1';
		
		if (($stmt = $mysqli->prepare($query)) === false) throw new Exception('Problem preparing records: '.$mysqli->error);
		
		$stmt->bind_param($col_types[$column], $args[0]);
		
		if (!$stmt->execute()) throw new Exception('Problem reading '.self::_tablename.': '.$stmt->error."\n$query");
		
		$bound_names = array();
		for ($i=0; $i < count($col_keys); $i++) { 
			$bound_name = $col_keys[$i];
			$$bound_name = '';
			$bound_names[] = &$$bound_name;
		}
		
		if (call_user_func_array(array($stmt, 'bind_result'), $bound_names) === false)
			throw new Exception('Problem reading '.self::_tablename.': '.$stmt->error);
		
		$records   = array();
		$classname = get_called_class();
		while ($stmt->fetch()) {
			$j = new $classname;
			
			for ($i=0; $i < count($col_keys); $i++) { 
				$j->$col_keys[$i] = $$col_keys[$i];
			}
			
			$records[] = $j;
		}
		
		$stmt->close();
		$mysqli->close();
		
		return $limit == 0 ? $records : $records[0];
	}
}


?>