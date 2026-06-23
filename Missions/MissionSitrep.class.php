<?php
declare(strict_types = 1);

namespace XCOMDatabank\Missions;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Management\Sitrep;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class MissionSitrep
{
	public ?int $id;
	public int $missionID;
	public int $sitrepID;
		
	function __construct() {
        $this->id = null;
		$this->missionID = 0;
		$this->sitrepID = 0;
	}
		
	public function newMissionSitrep($missionSitrep) {
        $this->validateMissionSitrep($missionSitrep);

        $query = "INSERT INTO xcom_mission_sitrep VALUES (NULL, :missionID, :sitrepID)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getMissionSitrep(int $id) {
        $query = "SELECT * FROM xcom_mission_sitrep WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->missionID = intval($row['mission_id']);
		$this->sitrepID = intval($row['sitrep_id']);
	}
		
	public function editMissionSitrep($missionSitrep) {
        $this->validateMissionSitrep($missionSitrep);

        $query = "UPDATE xcom_mission_sitrep SET mission_id = :missionID, sitrep_id = :sitrepID WHERE id = :id";
        $params = $this->getParams();
        $params[2] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateMissionSitrep(array $submit) : void {
        // If a Mission SITREP ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'mission_sitrep', false, "Mission SITREP ID");
        }

        // Make sure the mission ID is valid
        $this->missionID = Validate::testIndex($submit['mission_id'], 'mission', false, "Mission SITREP Mission");

        // Make sure the SITREP ID is valid
        $this->sitrepID = Validate::testIndex($submit['sitrep_id'], 'sitrep', false, "Mission SITREP SITREP");
    }

    public function processForm(array $submit, string $redirect = ''): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editMissionSitrep($submit);
            } else {
                return Error::returnError("Mission SITREP ID is set, but Mission SITREP ID is not numeric");
            }
        } else {
            $this->newMissionSitrep($submit);
        }
        if(!empty($redirect)) {
            header('Location: '.$redirect);
        }
        return "";
    }

    public static function getMissionSitrepForm(MissionSitrep $missionSitrep) : void {
        $id = $missionSitrep->id;
        if($id != "" and $id !== null and is_numeric($id)) {
            echo FieldHidden::getField('id',strval($id));
        }

        // Select Mission from Mission List
        echo FieldSelect::getField('mission', strval($missionSitrep->missionID), 'form-control', 'mission',
            'Mission', array('col-md-5','pb-2','form-floating'), true, Mission::getMissionArray())  ;

        // Select SITREP from SITREP List
        echo FieldSelect::getField('sitrep', strval($missionSitrep->sitrepID), 'form-control', 'sitrep',
            'Sitrep', array('col-md-4','pb-2','form-floating'), true, Sitrep::getSitrepArray())  ;
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_mission_sitrep ORDER BY id DESC";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table mission-sitrep">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Operation Name</div>'."\n";
        $listString .= '<div class="col-4">SITREP</div>'."\n";
        $listString .= '<div class="col-4"></div>'."\n";
        $listString .= '</div>'."\n";

        while ($row = $queryResult->fetch()) {

            $listString .= '<div class="row">'."\n";

            $listString .= '<div class="col-4">';
            $listString .= Mission::getOperationName($row['mission_id']);
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= '<a href="/mission/mission-sitrep.php?id='.$row['id'].'">'.Sitrep::getSitrepName($row['sitrep_id']).'</a>';
            $listString .= '</div>'."\n";


            $listString .= '<div class="col-4"></div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    private function getParams(): array
    {
        $params[0] = array("param" => ":missionID", "var" => $this->missionID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":sitrepID", "var" => $this->sitrepID, "type" => PDO::PARAM_INT,);
        return $params;
    }
}