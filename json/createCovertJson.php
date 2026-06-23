<?php

use XCOMDatabank\Utility\Database;

	function createCovertJson() {
        $query = "SELECT * FROM xcom_covert_type WHERE enabled = true ORDER BY name";
        $params = array();
        $queryResult = Database::runQuery('insert', $query, $params);
		
		$covertArray = [];
		
		while ($row = $queryResult->fetch()) {
			
			$covertArray[$row['name']] = [];
		}
		
		$fp = fopen(__DIR__.'../../admin.xcom-databank.games/json/covertActions.json', 'w');
		fwrite($fp, json_encode($covertArray));
		fclose($fp);
	}