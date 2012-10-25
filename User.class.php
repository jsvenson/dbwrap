<?php

require_once('DatabaseTable.class.php');

/**
* User
*/
class User extends DatabaseTable {
	var $_tablename  = 'users';
	const _tablename = 'users';

	protected $username = '';
	protected $password_hash = '';
	protected $password_salt = '';
	protected $last_login = '';
	protected $textcol = '';

	function __construct($id = 0) {
		$this->_types['username'] = 's';
		$this->_lazyload['username'] = false;
		$this->_types['password_hash'] = 's';
		$this->_lazyload['password_hash'] = false;
		$this->_types['password_salt'] = 's';
		$this->_lazyload['password_salt'] = false;
		$this->_types['last_login'] = 's';
		$this->_lazyload['last_login'] = false;
		$this->_types['textcol'] = 's';
		$this->_lazyload['textcol'] = true;

		parent::__construct($id);
	}


}

$u = User::find();
$u[1]->textcol;
print_r($u);

?>