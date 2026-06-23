<?php

declare(strict_types = 1);

namespace XCOMDatabank\Missions;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class Objective
{
	
	public ?int $id;
	public int $missionTypeID;
	public string $description;
    public bool $enabled;
    public ?string $notes;
		
	function __construct() {
        $this->id = null;
		$this->missionTypeID = 0;
		$this->description = "";
        $this->enabled = true;
        $this->notes = null;
	}
		
	public function newObjective(array $objective): void {
        $this->validateObjective($objective);

        $query = "INSERT INTO xcom_objective VALUES (NULL, :missionType, :description, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getObjective(int $id): void {
        $query = "SELECT * FROM xcom_objective WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

        $this->id = $id;
        $this->missionTypeID = intval($row['mission_type_id']);
        $this->description = $row['description'];
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
	}
		
	public function editObjective(array $objective): void {
        $this->validateObjective($objective);

        $query = "UPDATE xcom_objective SET mission_type_id = :missionType, description = :description, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[4] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateObjective(array $submit): void {
        // If an Objective ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'objective', false, "Objective ID");
        }

        // Make sure Mission Type ID exists and is valid
        $this->missionTypeID = Validate::testIndex($submit['mission_type_id'], 'mission_type', false, "Mission Type ID");

        // Make sure description is a valid, non-null string
        $this->description = Validate::testString($submit['description'], -1, -1, false, "Objective Description");

        // Make sure enabled is some form of boolean value
        $this->enabled = Validate::testTF($submit['enabled'], false, "Objective Enabled");

        // Make sure notes is a valid string or null
        $this->notes = Validate::testString($submit['notes'] ?? null, -1, -1, true, "Objective Notes");
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editObjective($submit);
            } else {
                return Error::returnError("Mission Type ID is set, but Mission Type ID is not numeric");
            }
        } else {
            $this->newObjective($submit);
        }
        header('Location: '.$redirect);
        return "";
    }
	
	public static function getMissionTypeByObjective($objective): int {

        $query = "SELECT mission_type_id FROM xcom_objective WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $objective, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        if($queryResult->rowCount() > 0) {
            $row = $queryResult->fetch();
        } else {
            $row['mission_type_id'] = -1;
        }

		return  (int)$row['mission_type_id'];
	}
	
	public static function getObjectiveList($missionTypeID): array {
        $objArray = array();
		if($missionTypeID === null or $missionTypeID == "") {
			return array(
                'text' => 'No Objectives Available',
                'value' => '',
            );
		}

        $query = "SELECT objective.description as description, objective.id as id
			FROM xcom_objective as objective
				INNER JOIN xcom_mission_type as type ON type.id = objective.mission_type_id
			WHERE type.id = :id
			ORDER BY objective.description";
        $params[0] = array("param" => ":id", "var" => $missionTypeID, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        if(!empty($queryResult)) {
            $list = $queryResult->fetchAll();
        } else {
            return array(
                'text' => 'No Objectives Available',
                'value' => '',
            );
        }
		
		foreach ($list as $row) {
            $printArray = array(
                'text' => $row['description'],
                'value' => $row['id'],
            );
            array_push($objArray, $printArray);
		}
		return $objArray;
	}

    public static function getObjectiveForm(Objective $objective) {
        $id = $objective->id;
        if($id != "" and $id !== null and is_numeric($id)) {
            echo FieldHidden::getField('id',strval($id));
        }

        echo FieldSelect::getField('mission_type_id', strval($objective->missionTypeID), 'form-control', 'mission-type',
            'Mission Type', array('col-md-3','pb-2','form-floating'), true, MissionType::getMissionTypeArray());

        // Print Mission Type Description Form Field
        echo FieldText::getField('description', $objective->description, 'form-control', 'description',
            'Description', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldSelect::getField('enabled', strval($objective->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-2','pb-2','form-floating'), true, Definitions::arrayYesNo());

        // Print Mission Types Notes Field
        echo FieldText::getField('notes', $objective->notes ?? null, 'form-control', 'notes',
            'Objective Notes', array('col-md-4','pb-2','form-floating'), false);
    }

    public static function getListPage(): void {
        $query = "SELECT objective.id, objective.description as objectiveDescription, type.description as typeDescription,
            objective.enabled as enabled, objective.notes as notes
			FROM xcom_objective as objective
				INNER JOIN xcom_mission_type as type ON type.id = objective.mission_type_id 
			ORDER BY objective.enabled DESC, type.description, objective.enabled DESC, objective.description";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table objective-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-3">Mission Type</div>'."\n";
        $listString .= '<div class="col-4">Objective</div>'."\n";
        $listString .= '<div class="col-2">Enabled</div>'."\n";
        $listString .= '<div class="col-3">Notes</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {

            if($row['enabled']) {
                $rowClass = "row-green";
            } else {
                $rowClass = "row-red";
            }

            $listString .= '<div class="row '.$rowClass.'">'."\n";
            $listString .= '<div class="col-3">';
            $listString .= $row['typeDescription'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= '<a href="/mission/objective.php?id='.$row['id'].'">'.$row['objectiveDescription'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';

            if($row['enabled']) {
                $listString .= "Enabled";
            } else {
                $listString .=  "Disabled";
            }

            $listString .= '</div>'."\n";
            $listString .= '<div class="col-3">';
            $listString .= $row['notes'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    private function getParams(): array {
        $params[0] = array("param" => ":missionType", "var" => $this->missionTypeID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":description", "var" => $this->description, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[3] = array("param" => ":notes", "var" => $this->notes, "type" => PDO::PARAM_STR,);
        return $params;
    }
}