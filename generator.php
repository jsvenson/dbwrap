#!/usr/bin/php
<?php

require_once('Inflector.class.php');
require_once('dbConstants.class.php');

if (!isset($argv[1]) || $argv[1] == '?' || $argv[1] == 'help') {
	echo "\n" . 'Usage: generator -d<database> -t<table> [--classname=<classname>]' . 
		 "\n" . '         [--has-many=<referent-class>] [--scaffold] [--scaffold-only]'."\n\n";
	exit();
}

$strings   = array('varchar', 'longtext', 'text', 'char', 'date', 'enum', 'mediumtext', 'timestamp', 'time');
$integer   = array('bigint', 'int', 'binary', 'tinyint');
$decimal   = array('decimal');

$ignore_columns = array('id', 'created', 'updated');

$data_types = array(
	'bigint'     => 'i',
	'binary'     => 'i',
	'bit'        => 'i',
	'blob'       => 'b',
	'char'       => 's',
	'date'       => 's',
	'datetime'   => 's',
	'decimal'    => 'd',
	'enum'       => 's',
	'int'        => 'i',
	'longblob'   => 'b',
	'longtext'   => 's',
	'mediumblob' => 'b',
	'mediumtext' => 's',
	'set'        => 's',
	'smallint'   => 'i',
	'text'       => 's',
	'time'       => 's',
	'timestamp'  => 's',
	'tinyblob'   => 'b',
	'tinyint'    => 'i',
	'tinytext'   => 's',
	'varchar'    => 's'
);


$options = getopt('d:t:', array('classname::', 'has-many::', 'scaffold', 'scaffold-only'));

$dbname    = $options['d'];
$table     = strtolower($options['t']);
$classname = Inflector::classify(isset($options['classname']) ? $options['classname'] : $table);

$mysqli = new MySQLi(dbConstants::_dbserver, dbConstants::_dbuser, base64_decode(dbConstants::_dbpass), 'information_schema');

try {
	if (mysqli_connect_error())
		throw new Exception('Connection error: '.mysqli_connect_error());

	if (!($stmt = $mysqli->prepare('select column_name, column_default, data_type from columns where table_schema=? and table_name=?')))
		throw new Exception('Problem preparing statment: '.$mysqli->error);
	
	$stmt->bind_param('ss', $dbname, $table);
	
	if (!$stmt->execute()) throw new Exception('Problem executing statement: '.$stmt->error);
	
	$stmt->bind_result($column_name, $column_default, $data_type);
	
	$columns     = array();
	$column_list = array();
	while ($stmt->fetch()) {
		$column_list[] = $column_name;
		if (!in_array($column_name, $ignore_columns)){
			$columns[] = array(
				'name'=>strtolower($column_name),
				'default'=>strtolower($column_default),
				'type'=>strtolower($data_type)
			);
		}
	}
	
	if (isset($options['scaffold']) || isset($options['scaffold-only'])) {
		$template = file_get_contents('form_template.txt');
		
		$classname_title = Inflector::titleize($classname);
		$classname_parameter = Inflector::parameterize($classname_title, '_');
		$classname_parameter_plural = Inflector::pluralize($classname_parameter);
		
		$replace = array(
			'%%CLASSNAME%%',
			'%%CLASSNAME_PARAMETER%%',
			'%%CLASSNAME_PARAMETER_PLURAL%%',
			'%%CLASSNAME_TITLE%%',
			'%%CLASSNAME_TITLE_LOWER%%',
			'%%FIELD_COUNT%%'
		);
		
		$with = array(
			$classname,
			$classname_parameter,
			$classname_parameter_plural,
			$classname_title,
			strtolower($classname_title),
			count($column_list)
		);
		
		$template = str_replace($replace, $with, $template);

		# loop through each %%BEGIN FIELD_LOOP%%
        $pattern = '/\s+%%BEGIN FIELD_LOOP%%([\w\W]+?)\n\s*%%END FIELD_LOOP%%/';
        $template = preg_replace_callback($pattern, function($matches) use ($column_list) {
            $str = '';
            for ($i=0; $i < count($column_list); $i++) { 
                $fieldname_title = Inflector::titleize($column_list[$i]);
                $fieldname_parameter = Inflector::parameterize($fieldname_title, '_');
                $str .= str_replace(array(
                    '%%FIELDNAME%%', '%%FIELDNAME_PARAMETER%%', '%%FIELDNAME_TITLE%%'
                ), array(
                    $column_list[$i], $fieldname_parameter, $fieldname_title
                ), $matches[1]);
            }
        	return $str;
        }, $template);
		
		$result = file_put_contents($classname_parameter_plural . '.php', $template);
		
		if ($result === false) throw new Exception('Problem writing form template.');
	}
	
	if (!isset($options['scaffold-only'])) {
		$template = file_get_contents('template.txt');
	
		$properties            = array();
		$type_defs             = array();
		$bind_columns          = array();
		$collections           = array();
	
		foreach ($columns as $c) {
			switch ($data_types[$c['type']]) {
				case 'i':
				case 'd':
					$val = '0';
					break;
				case 'b':
				case 's':
					$val = "''";
					break;
			}
		
			$properties[]   = 'var $'.$c['name'].' = '.$val.';';
			$type_defs[]    = "\$this->_types['{$c['name']}'] = '{$data_types[$c['type']]}';";
	        // $type_defs[]    = "\$this->_lazyload['{$c['name']}'] = ".(delayed_load($c['type']) ? 'true' : 'false').";";
			$bind_columns[] = '$j->'.$c['name'].' = $'.$c['name'].';';
		}
	
		if (isset($options['has-many'])) {
			if (!is_array($options['has-many'])) $options['has-many'] = array($options['has-many']);
		
			for ($i=0; $i < count($options['has-many']); $i++) { 
				$m = $options['has-many'][$i];
				$properties[] = 'var $_'.$m.' = array();';
				$collections[] = "\tpublic function " . $m . "() {\n\t\tif (\$this->_" . $m . " == array()) \$this->_" . $m
					. " = new Collection('" . $m . "', array('" . Inflector::foreign_key($classname)
					. "' => \$this->id));\n\t\treturn \$this->_" . $m . ";\n\t}";
			}
		}
	
		$result = file_put_contents($classname.'.class.php',
			str_replace(
				array(
					'%%TABLENAME%%',
					'%%PROPERTIES%%',
					'%%TYPE_DEFS%%',
					'%%CLASSNAME%%',
					'%%COLUMN_LIST%%',
					'%%BIND_COLUMNS%%',
					'%%COLUMN_VAR_LIST%%',
					'%%COLLECTIONS%%'
				), array(
					strtolower($table),
					implode("\n\t", $properties),
					implode("\n\t\t", $type_defs)."\n",
					$classname,
					'`'.implode('`, `', $column_list).'`',
					implode("\n\t\t\t", $bind_columns),
					'$'.implode(', $', $column_list),
					"\n\n".implode("\n\n", $collections)
				), $template));
	
		if ($result === false) throw new Exception('Problem writing file.');
	}
} catch (Exception $e) {
	echo "Generation of class failed.\n".$e->getMessage()."\n";
}

function delayed_load($type) {
    return array_search($type, array(
	   'tinyblob', 'blob', 'mediumblob', 'longblob',
	   'tinytext', 'text', 'mediumtext', 'longtext'
	));
}

?>