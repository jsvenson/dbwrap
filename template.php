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