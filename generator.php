#!/usr/bin/php
<?php

require_once('Inflector.class.php');

if (!isset($argv[1]) || $argv[1] == '?' || $argv[1] == 'help') {
	echo "\n".'Usage: generator <database> <table> [<classname>]'."\n\n";
	exit();
}

$dbhost    = 'localhost';
$dbuser    = 'root';
$dbpass    = base64_decode('b2Jlcm9uMTI=');

$strings   = array('varchar', 'longtext', 'text', 'char', 'date', 'enum', 'mediumtext', 'timestamp', 'time');
$integer   = array('bigint', 'int', 'binary', 'tinyint');
$decimal   = array('decimal');

$ignore_columns = array('id', 'created', 'updated');

$data_types = array(
	'bigint'     => 'i',
	'binary'     => 'i',
	'blob'       => 'b',
	'char'       => 's',
	'date'       => 's',
	'datetime'   => 's',
	'decimal'    => 'd',
	'enum'       => 's',
	'int'        => 'i',
	'longblob'   => 'b',
	'mediumblob' => 'b',
	'mediumtext' => 's',
	'set'        => 's',
	'smallint'   => 'i',
	'text'       => 's',
	'time'       => 's',
	'timestamp'  => 's',
	'tinyint'    => 'i',
	'varchar'    => 's'
);

$dbname    = strtolower($argv[1]);
$table     = strtolower($argv[2]);
$classname = Inflector::classify(isset($argv[3]) ? $argv[3] : $table);

$mysqli = new MySQLi($dbhost, $dbuser, $dbpass, 'information_schema');

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
	
	$template = file_get_contents('template.php');
	
	$properties            = array();
	$type_defs             = array();
	$bind_columns          = array();
	
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
		
		$properties[]            = 'var $'.$c['name'].' = '.$val.';';
		$type_defs[]             = "\$this->_types['{$c['name']}'] = '{$data_types[$c['type']]}';";
		$bind_columns[]          = '$j->'.$c['name'].' = $'.$c['name'].';';
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
				'%%COLUMN_VAR_LIST%%'
			), array(
				strtolower($table),
				implode("\n\t", $properties),
				implode("\n\t\t", $type_defs)."\n",
				$classname,
				'`'.implode('`, `', $column_list).'`',
				implode("\n\t\t\t", $bind_columns),
				'$'.implode(', $', $column_list)
			), $template));
	
	if ($result === false) throw new Exception('Problem writing file.');
} catch (Exception $e) {
	echo "Generation of class failed.\n".$e->getMessage()."\n";
}

?>