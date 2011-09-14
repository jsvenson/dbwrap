<?php

require_once('DatabaseTable.class.php');

/**
* %%CLASSNAME%%
*/
class %%CLASSNAME%% extends DatabaseTable {
	var $_tablename  = '%%TABLENAME%%';
	const _tablename = '%%TABLENAME%%';

	%%PROPERTIES%%

	function __construct($id = 0) {
		%%TYPE_DEFS%%
		parent::__construct($id);
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
		
		$query = 'select column_name, data_type from information_schema.columns where table_schema=\''.dbConstants::_dbname.'\' and table_name=\''.self::_tablename.'\'';
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
			. self::_tablename.'` where `' . $mysqli->real_escape_string($column) . '` = ?';
		
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
			$bound_names[$col_keys[$i]] = &$$bound_name;
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
	
	public static function find($opts = array()) {
		$mysqli = new MySQLi(
			dbConstants::_dbserver,
			dbConstants::_dbuser,
			base64_decode(dbConstants::_dbpass),
			dbConstants::_dbname
		);
		
		$limit   = isset($opts['limit'])  ? $opts['limit']  : 0;
		$offset  = isset($opts['offset']) ? $opts['offset'] : 0;
		$order   = isset($opts['order'])  ? $opts['order']  : '';
		
		$types = '';
		$param = array();
		$query = 'select %%COLUMN_LIST%% from `'.self::_tablename.'` where 1=1';
		
		if ($order != '') {
			$query .= ' order by '.$mysqli->real_escape_string($order);
		}
		
		if ($limit > 0) {
			$query .= ' limit ? offset ?';
			$param[] = array('i', $limit);
			$param[] = array('i', $offset);
		}
		
		$stmt = $mysqli->prepare($query);
		if ($stmt === false) {
			throw new Exception('Problem preparing records: '.$mysqli->error);
		}
		
		if (count($param) > 0) {
			$types  = '';
			$params = array();
			
			for ($i=0; $i < count($param); $i++) { 
				$types .= $param[$i][0];
				$params[] = $param[$i][1];
			}
			
			$bind_names[] = $types;
			// TODO: Rewrite this so I understand what's going on
			for ($i=0; $i<count($params);$i++) {
			    $bind_name = 'bind' . $i;
			    $$bind_name = $params[$i];
			    $bind_names[] = &$$bind_name;
			}
			
			$return = call_user_func_array(array($stmt,'bind_param'),$bind_names);
			
			if (!$return) throw new Exception('Problem with param binding: '.$stmt->error);
		}
		
		if (!$stmt->execute()) throw new Exception('Problem reading '.self::_tablename.': '.$stmt->error."\n$query");
		
		if ($stmt->bind_result(%%COLUMN_VAR_LIST%%) === false)
			throw new Exception('Problem reading '.self::_tablename.': '.$stmt->error);
		
		$records = array();
		while ($stmt->fetch()) {
			$j = new %%CLASSNAME%%();
			$j->id = $id;
			$j->created = $created;
			$j->updated = $updated;
			%%BIND_COLUMNS%%
			
			$records[] = $j;
		}
		
		$mysqli->close();
		
		return $records;
	}
}

?>