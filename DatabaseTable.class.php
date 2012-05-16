<?php

require_once('Inflector.class.php');
require_once('dbConstants.class.php');
require_once('Collection.class.php');

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
	var $_lazyload   = array(); # boolean array for column delayed loading
	
	function __construct($id = 0) {
		$this->_types['id'] = 'i';
		$this->_types['created'] = 's';
		$this->_types['updated'] = 's';
		
		if (is_array($id)) {
			$cols = array_filter(array_keys(get_class_vars(get_called_class())), function($el) {
				return $el[0] != '_';
			});
			
			foreach ($cols as $c) {
				if (isset($id[$c])) $this->$c = $id[$c];
			}
		} else {
			if ($id > 0) $this->load($id);
		}
	}
	
    // public function __get($varname) {
    //  # TODO: if $this->_lazyload($varname) then pull data from database
    // }
	
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
		
		if (!($stmt = $this->_db->prepare('delete from `'.$this->_tablename.'` where id = ?')))
			throw new Exception('Problem preparing delete statement: '.$this->_db->error);
		
		if (!$stmt->bind_param('i', $this->id))
			throw new Exception('Problem binding parameter for delete: '.$stmt->error);
		
		if (!$stmt->execute())
			throw new Exception ('Problem deleting record: '.$stmt->error);
		
		$this->closeConnection();
		
		$this->id = 0;
		
		return true;
	}
	
	public function load($id = 0) {
		if ($id == 0) return false;
		
		$this->openConnection();
		
		$columns = '`'.implode('`,`', $this->columns()).'`';
		
		# TODO: filter $columns for $this->lazyload[]
		if (!($stmt = $this->_db->prepare('select '.$columns.' from `'.$this->_tablename.'` where id = ?')))
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
			
			if (!($stmt = $this->_db->prepare('insert into `'.$this->_tablename.'` ('.$cols.') values ('.$values.')')))
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
			
			# TODO: if $this->_lazyload[column] and column == null, don't update that column
			
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
	
	private static function getColumns() {
        $col_types = array();
        
        $data_types = array(
			'bigint' => 'i', 'binary' => 'i', 'bit' => 'i', 'blob' => 'b', 'char' => 's', 'date' => 's', 'datetime' => 's',
			'decimal' => 'd', 'enum' => 's', 'int' => 'i', 'longblob' => 'b', 'mediumblob' => 'b', 'mediumtext' => 's',
			'set' => 's', 'smallint' => 'i', 'text' => 's', 'time' => 's', 'timestamp' => 's', 'tinyint' => 'i', 'varchar' => 's'
		);
		
		$mysqli = new MySQLi(
			dbConstants::_dbserver,
			dbConstants::_dbuser,
			base64_decode(dbConstants::_dbpass),
			dbConstants::_dbname
		);
		
		$classname = get_called_class();
		$tablename = $classname::_tablename;
		$database  = dbConstants::_dbname;
		
		$query = 'select column_name, data_type from information_schema.columns where table_schema=? and table_name=?';
		$stmt = $mysqli->prepare($query);
		$stmt->bind_param('ss', $database, $tablename);
		
		$stmt->execute();
		
		$stmt->bind_result($column_name, $data_type);
		while ($stmt->fetch()) {
		    $col_types[$column_name] = $data_types[$data_type];
		}
		$stmt->close();
		
		return $col_types;
	}
	
    # currently handles [find|count][_by_|_all_by_<column_name>]
    # find('first|all', array(
    #   ['conditions'=>'<conditions>', 'values'=>array(<values),]
    #   ['order'=>'<order>',]
    #   ['page'=><1-based page number>, 'per_page'=><records per page>,]
    # ));
	public static function __callStatic($name, $args) {
        $name = strtolower($name);
        $command = explode('_', $name, 3); # get method name parts

        $class = get_called_class();
        $cols = array();

        # for a count, select count(*), otherwise select all columns in table
        if ($command[0] == 'count') { 
            $query = 'select count(*) as \'total\' from `'.$class::_tablename.'`';
        } else {
            $columns = self::getColumns();
            $query = 'select `'.implode('`,`', array_keys($columns)).'` from `'.$class::_tablename.'`';
        }

        $mysqli = new MySQLi(
            dbConstants::_dbserver,
            dbConstants::_dbuser,
            base64_decode(dbConstants::_dbpass),
            dbConstants::_dbname
        );

        # build where clause
        if (count($command) > 1 || isset($args[1]['conditions'])) {
            if (!isset($columns)) $columns = self::getColumns();

            if (count($command) > 1) {
                $by_column = $command[1] == 'by' ? $command[2] : 
                    substr($command[2], strpos($command[2], '_') + 1);
                
                $query .= ' where ' . $mysqli->real_escape_string($by_column) . '=?';
            } else {
                # find all the columns in conditions
                # supports =, !=, <, >, <=, >=
                preg_match_all('/`?\w+?`?\s*(=|<|>|!=|<=|>=)\s*\?/', $args[1]['conditions'], $matches);
                
                # $cols = the columns listed in conditions
                $cols = $matches[0];
    			array_walk($cols, function(&$el) {
                    $split = preg_split('/(=|<|>|!=|<=|>=)/', $el);
    				$el = str_replace('`', '', trim($split[0]));
    			});

    			$query .= ' where ' . $args[1]['conditions'];
    			
    			# make sure all columns in conditions exist in the table
    			foreach ($cols as $c) {
                    if (array_search($c, array_merge(array_keys($columns), array('id', 'created', 'updated'))) === false)
                        throw new Exception('Unknown column “' . $c . '”.');
    			}
            }
        }
        
        # add order by and limit clauses
        if ($command[0] == 'find') {
            # set order by
            if (isset($args[1]['order'])) $query .= ' order by '.$mysqli->real_escape_string($args[1]['order']);
            else $query .= ' order by `created` asc';

            # set limits
            # if 'first' was specified or if find_by or count_by was called, limit to one result
            if ((isset($args[0]) && ($args[0] == 'first' || $args[0] == ':first')) || 
                ((count($command) > 1) && $command[1] == 'by')) $query .= ' limit 1';
            elseif (isset($args[1]['page'])) { # if paging, limit to 'per_page' value
                $limit_value = (int)$args[1]['per_page'];
                $offset_value = ((int)$args[1]['page'] - 1) * $limit_value;
                if ($offset_value < 0) $offset_value = 0; # never have a negative offset
                $query .= ' limit ' . $limit_value . ' offset ' . $offset_value;
            }
        }

        # prepare query
        if (($stmt = $mysqli->prepare($query)) === false)
            throw new Exception('Problem preparing records: '.$mysqli->error);
        
        # bind variables
        if (count($cols) > 0) { # bind 'conditions'
            $types = '';
            $params = array();
            
            for ($i=0; $i < count($cols); $i++) { 
                $types .= $columns[$cols[$i]];
                $params[] = $args[1]['values'][$i];
            }
            
            $bind_names[] = $types;
            for ($i=0; $i < count($params); $i++) { 
                $bind_name = 'bind'.$i;
                $$bind_name = $params[$i];
                $bind_names[] = &$$bind_name;
            }
            
            call_user_func_array(array($stmt, 'bind_param'), $bind_names);
        }
        
        if (count($command) > 1) { # bind _by_colname
            $stmt->bind_param($columns[$by_column], $args[0]);
        }

        # execute query
        $classname = get_called_class();
        if (!$stmt->execute()) throw new Exception('Problems reading ' . $classname::_tablename . ': '
            . $stmt->error . "\n$query");

        # if find, loop through results. If limit 1, return object, else return array of objects
        if ($command[0] == 'find') {
            $bound_names = array();
            $col_keys = array_keys($columns);
            for ($i=0; $i < count($columns); $i++) { 
                $bound_name = $col_keys[$i];
                $$bound_name = '';
                $bound_names[] = &$$bound_name;
            }
            
            if (call_user_func_array(array($stmt, 'bind_result'), $bound_names) === false)
                throw new Exception('Problem reading ' . $classname::_tablename . ': ' . $stmt->error);
            
            $records = array();
            while ($stmt->fetch()) {
                $obj = new $classname;
                
                for ($i=0; $i < count($col_keys); $i++) { 
                    $obj->$col_keys[$i] = $$col_keys[$i];
                }
                
                $records[] = $obj;
            }
        } else { # count
            $stmt->bind_result($total);
            $stmt->fetch();
            $count_result = $total;
        }
        
        $stmt->close();
        $mysqli->close();
        
        return isset($count_result) ? $count_result : $records;
    }
}

?>