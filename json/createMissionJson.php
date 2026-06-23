<?php

use XCOMDatabank\Utility\Database;

function createMissionJson()
{

    $query = "SELECT * FROM xcom_mission_type WHERE enabled = true ORDER BY description";
    $params = array();
    $queryResult = Database::runQuery('insert', $query, $params);

    $missionArray = [];

    while ($row = $queryResult->fetch()) {

        $missionArray[$row['description']] = [];

        $query = "SELECT id, description FROM xcom_objective WHERE enabled = true and mission_type_id = :id";
        $params[0] = array("param" => ":id", "var" => $row['id'], "type" => PDO::PARAM_INT,);
        $queryResult2 = Database::runQuery('insert', $query, $params);

        while ($row2 = $queryResult2->fetch()) {
            $missionArray[$row['description']][$row2['id']] = $row2['description'];
        }

        $fp = fopen(__DIR__.'../../admin.xcom-databank.games/json/missions.json', 'w');
        fwrite($fp, json_encode($missionArray));
        fclose($fp);
    }
}