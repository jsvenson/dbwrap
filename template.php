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
}

?>