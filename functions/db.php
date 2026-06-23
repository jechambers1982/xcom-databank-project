<?php

	function openDatabase() {
		try{
			$ini = "/home/joshch9/project/config/db.ini";
			$parse = parse_ini_file($ini,true);
			
			$host = $parse["db_host"];
			$port = $parse["db_port"];
			$dbname = $parse["db_name"];
			$user = $parse["db_user"];
			$password = $parse["db_password"];
			$options = $parse["db_options"];
			$attributes = $parse["db_attributes"];
			
			$dsn = "mysql:host=".$host.";port=".$port.";dbname=".$dbname;
			
			$dbh = new PDO($dsn, $user, $password, $options);
			
			foreach ( $attributes as $k => $v ) {
				$dbh->setAttribute( constant("PDO::$k"),constant("PDO::$v"));
			}
			
			return $dbh;
		}
		catch (PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
			die();
		}
	}
	
	function closeDatabase($dbh, $sth) {
		return null;
	}