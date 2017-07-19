<?php

class DB {
	public static $sql='';

	function query($sql){
		global $dbh;
		self::$sql=$sql;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		self::$sql=$sql;
		return $dbh->exec($sql);
	}

	function prepare($sql){
		global $dbh;
		self::$sql=$sql;
		return $dbh->prepare($sql);
	}

	function lastInsertId() {
		global $dbh;
		return $dbh->lastInsertId();
	}
	
	function errorInfo() {
		global $dbh;
		return $dbh->errorInfo();
	}

	function errorCode(){
		$msg=self::errorInfo();
		return $msg[0];
	}

	function errorMessage(){
		$msg=self::errorInfo();
		return $msg[2];
	}

	// Pass the sql to include it with the output
	function logError(){
		global $dbh;
		$trace=debug_backtrace();
		$caller=(isset($trace[1]))?$trace[1]:array('function' => 'direct');
		$function=$caller['function'];
		$class=($function=='direct')?'direct':$caller['class'];
		$sqloutput=(self::$sql)?" SQL=".self::$sql:'';

		error_log("PDO Error $class::$function ".self::errorCode().":".self::errorMessage().$sqloutput);
	}
}

?>
