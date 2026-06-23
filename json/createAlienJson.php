<?php

use XCOMDatabank\Utility\Database;

	function createAlienJson() {
        $query = "SELECT * FROM xcom_alien_type WHERE enabled = true ORDER BY faction, name";
        $params = array();
        $queryResult = Database::runQuery('insert', $query, $params);
		
		$alienArray = [];
		
		while ($row = $queryResult->fetch()) {
			
			$alienArray[$row['name']] = [];

            $query = 'SELECT id, name FROM xcom_aliens WHERE type_id = :id and enabled = true';
            $params[0] = array("param" => ":id", "var" => $row['id'], "type" => PDO::PARAM_INT,);
            $queryResult2 = Database::runQuery('insert', $query, $params);
			
			while($row2 = $queryResult2->fetch()) {
				$alienArray[$row['name']][$row2['id']] = $row2['name'];
			}
		}
		$fp = fopen(__DIR__.'../../admin.xcom-databank.games/json/aliens.json', 'w');
		fwrite($fp, json_encode($alienArray));
		fclose($fp);
	}