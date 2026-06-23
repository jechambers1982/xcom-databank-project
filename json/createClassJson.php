<?php

use XCOMDatabank\Utility\Database;

	function createClassJson() {

        $query = "SELECT * FROM xcom_class WHERE enabled = 1 ORDER BY name";
        $params = array();
        $queryResult = Database::runQuery('insert', $query, $params);
		
		$classArray = [];
		
		while ($row = $queryResult->fetch()) {
			
			$classArray[$row['name']] = [];
			$classArray[$row['name']]['id'] = $row['id'];

            $query = 'SELECT xrank.id as id, xrank.name as name, xrank.level as level FROM xcom_rank as xrank
				INNER JOIN xcom_class_rank as a ON a.rank_id = xrank.id			
				INNER JOIN xcom_class as class ON a.class_id = class.id 
			WHERE class.id = :id ORDER BY xrank.level';
            $params[0] = array("param" => ":id", "var" => $row['id'], "type" => PDO::PARAM_INT,);
            $queryResult2 = Database::runQuery('insert', $query, $params);
			
			while($row2 = $queryResult2->fetch()) {
				$classArray[$row['name']]['rank'][$row2['id']] = $row2['name'];
			}

            $query = 'SELECT skill.id as id, skill.name as name FROM xcom_skills as skill 
				INNER JOIN xcom_class_skill as a ON a.skill_id = skill.id 
				INNER JOIN xcom_class as class ON a.class_id = class.id 
			WHERE class.id = :id ORDER BY skill.name';
            $params[0] = array("param" => ":id", "var" => $row['id'], "type" => PDO::PARAM_INT,);
            $queryResult3 = Database::runQuery('insert', $query, $params);
			
			$classArray[$row['name']]['skills'] = [];
			
			while($row2 = $queryResult3->fetch()) {
				$classArray[$row['name']]['skills'][$row2['id']] = $row2['name'];
			}
		}
		
		$fp = fopen(__DIR__.'../../admin.xcom-databank.games/json/classes.json', 'w');
		fwrite($fp, json_encode($classArray));
		fclose($fp);
	}