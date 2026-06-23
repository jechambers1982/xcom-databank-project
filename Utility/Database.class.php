<?php

declare(strict_types = 1);

namespace XCOMDatabank\Utility;

use PDO;
use PDOException;

class Database
{
	private static function openDatabase() {
		try{
			$ini = __DIR__."/../config/db.ini";
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
			
			foreach ( $attributes as $k => $v )
				$dbh->setAttribute( constant("PDO::$k"),constant("PDO::$v"));
			
			return $dbh;
		}
		catch (PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br/>";
			die();
		}
	}
	
	public static function runQuery(string $type, string $query, array $parameters) {
		$dbh = self::openDatabase();
		$stmt = $dbh->prepare($query);
		
		foreach($parameters as $value){
			$stmt->bindValue($value['param'], $value['var'], $value['type']);
		}
		$stmt -> execute();
		
		$returned = null;
		
		if($type == "insert") {
			$returned = intval($dbh->lastInsertId());
		} elseif($type == "select") {
            $returned = $stmt;
		} elseif($type == "update") {
			$returned = true;
		}

        $stmt = null;
		$dbh = null;
		
		return $returned;
	}
}