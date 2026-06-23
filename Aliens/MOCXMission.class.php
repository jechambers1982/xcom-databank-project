<?php
declare(strict_types = 1);

namespace XCOMDatabank\Aliens;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Missions\Mission;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class MOCXMission
{
	public ?int $id;
		
	public int $mocxID; 		// database id from xcom_mocx
	public int $missionID; 		// database id from xcom_mission
	public int $shotsTaken; 	// shots taken
	public int $shotsHit; 		// shots hit
	public int $damage;			// damage dealt
	public int $killed;			// XCOM soldiers killed
    public int $killedOthers;   // Non XCOM, non Lost killed (usually civilians)
	public int $killedLost;		// Lost killed
	public string $status;		// Injury Status
		
	function __construct() {
        $this->id = null;
		$this->mocxID = -1;
		$this->missionID = -1;
		$this->shotsTaken = 0;
		$this->shotsHit = 0;
		$this->damage = 0;
		$this->killed = 0;
        $this->killedOthers = 0;
		$this->killedLost = 0;
		$this->status = "";
	}
		
	public function newMOCXMission($mocxMission)
	{
        $this->validateMOCXMission($mocxMission);

        $query = "INSERT INTO xcom_mocx_mission VALUES (NULL, :mocxID, :missionID, :shotsTaken, :shotsHit, :damage, :killed, :killedOthers, :killedLost, :status)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getMOCXMission(int $id)
	{
        $query = "SELECT * FROM xcom_mocx_mission WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->mocxID = intval($row['mocx_id']);
		$this->missionID = intval($row['mission_id']);
		$this->shotsTaken = intval($row['shots_taken']);
		$this->shotsHit = intval($row['shots_hit']);
		$this->damage = intval($row['damage']);
		$this->killed = intval($row['killed']);
        $this->killedOthers = intval($row['killed_others']);
		$this->killedLost = intval($row['killed_lost']);
		$this->status = $row['status'];
	}
		
	public function editMOCXMission($mocxMission) 
	{
        $this->validateMOCXMission($mocxMission);

        $query = "UPDATE xcom_mocx_mission SET mocx_id = :mocxID, mission_id = :missionID, shots_taken = :shotsTaken, 
                        shots_hit = :shotsHit, damage = :damage, killed = :killed, killed_others = :killedOthers, killed_lost = :killedLost, status = :status WHERE id = :id";
        $params = $this->getParams();
        $params[9] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateMOCXMission(array $submit): void {
        // If an MOCX Mission ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'mocx_mission', false, "MOCX Mission ID");
        }

        // MOCX ID
        $this->mocxID = Validate::testIndex($submit['mocx_id'], 'mocx', false, "MOCX ID");

        // Mission ID
        $this->missionID = Validate::testIndex($submit['mission_id'], 'mission', false, "Mission ID");

        // Shots Hit
        $this->shotsHit = Validate::testInteger((int)$submit['shots_hit'], 0, 20, false, "MOCX Shots Hit");

        // Shots Taken
        $this->shotsTaken = Validate::testInteger((int)$submit['shots_taken'], 0, 20, false, "MOCX Shots Taken");

        // Damage
        $this->damage = Validate::testInteger((int)$submit['damage'], 0, 200, false, "MOCX Damage");

        // Kills
        $this->killed = Validate::testInteger((int)$submit['killed'], 0, 10, false, "MOCX Kills");

        // Other Kills
        $this->killedOthers = Validate::testInteger((int)$submit['killed_others'], 0, 10, false, "MOCX Other Kills");

        // Lost Kills
        $this->killedLost = Validate::testInteger((int)$submit['killed_lost'], 0, 50, false, "MOCX Lost Kills");

        // Status
        $this->status = Validate::testArray($submit['status'], Definitions::getMOCXMissionStatus(), false, "MOCX Mission Status");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":mocxID", "var" => $this->mocxID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":missionID", "var" => $this->missionID, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":shotsHit", "var" => $this->shotsHit, "type" => PDO::PARAM_INT,);
        $params[3] = array("param" => ":shotsTaken", "var" => $this->shotsTaken, "type" => PDO::PARAM_INT,);
        $params[4] = array("param" => ":damage", "var" => $this->damage, "type" => PDO::PARAM_INT,);
        $params[5] = array("param" => ":killed", "var" => $this->killed, "type" => PDO::PARAM_INT,);
        $params[6] = array("param" => ":killedOthers", "var" => $this->killedOthers, "type" => PDO::PARAM_INT,);
        $params[7] = array("param" => ":killedLost", "var" => $this->killedLost, "type" => PDO::PARAM_INT,);
        $params[8] = array("param" => ":status", "var" => $this->status, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editMOCXMission($submit);
            } else {
                return Error::returnError("MOCX Mission ID is set, but MOCX Mission ID is not numeric");
            }
        } else {
            $this->newMOCXMission($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getMOCXMissionForm(MOCXMission $mocxMission)
    {
        if (!empty($mocxMission->id) and is_numeric($mocxMission->id)) {
            echo FieldHidden::getField('id', strval($mocxMission->id));
        }

        echo FieldSelect::getField('mocx_id', strval($mocxMission->mocxID), 'form-control', 'mocx-id',
            'MOCX Soldier', array('col-md-4', 'pb-2', 'form-floating'), true, MOCX::getMOCXList());

        echo FieldSelect::getField('mission_id', strval($mocxMission->missionID), 'form-control', 'mission-id',
            'Mission', array('col-md-4', 'pb-2', 'form-floating'), true, Mission::getMissionArray());

        echo FieldSelect::getField('status', $mocxMission->status, 'form-control', 'status',
            'Status', array('col-md-4', 'pb-2', 'form-floating'), true, Definitions::getMOCXMissionStatus());


        echo FieldTextNum::getField('shots_hit', strval($mocxMission->shotsHit), 'form-control', 'shots-hit',
            'Hit', array('col-md-2', 'pb-2', 'form-floating'), true, false, '0', '20');

        echo FieldTextNum::getField('shots_taken', strval($mocxMission->shotsTaken), 'form-control', 'shots-taken',
            'Taken', array('col-md-2', 'pb-2', 'form-floating'), true, false, '0', '20');

        echo FieldTextNum::getField('damage', strval($mocxMission->damage), 'form-control', 'damage',
            'Damage', array('col-md-2', 'pb-2', 'form-floating'), true, false, '0', '200');

        echo FieldTextNum::getField('killed', strval($mocxMission->killed), 'form-control', 'killed',
            'Killed', array('col-md-2', 'pb-2', 'form-floating'), true, false,'0', '10');

        echo FieldTextNum::getField('killed_others', strval($mocxMission->killedOthers), 'form-control', 'killed-others',
            'Others', array('col-md-2', 'pb-2', 'form-floating'), true, false,'0', '10');

        echo FieldTextNum::getField('killed_lost', strval($mocxMission->killedLost), 'form-control', 'killed-lost',
            'Lost', array('col-md-2', 'pb-2', 'form-floating'), true, false,'0', '50');

    }

    public static function getListPage(): void {
        $query = "SELECT mocx_mission.id as id, mocx.first_name as firstName, mocx.nickname as nickname, mocx.last_name as lastName, mission.operation_name as name
			FROM xcom_mocx_mission as mocx_mission 
				INNER JOIN xcom_mission as mission ON mocx_mission.mission_id = mission.id 
				INNER JOIN xcom_mocx as mocx ON mocx_mission.mocx_id = mocx.id 
			ORDER BY mission.mission_date DESC, mocx.last_name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-2">Edit</div>'."\n";
        $listString .= '<div class="col-5">Mission</div>'."\n";
        $listString .= '<div class="col-5">MOCX</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            $mocxName = $row['firstName'];
            if($row['nickname'] != "" and $row['nickname'] != null)
                $mocxName .= ' "'.$row['nickname'].'"';
            $mocxName .= " ".$row['lastName'];

            $listString .= '<div class="row">'."\n";
            $listString .= '<div class="col-2">';
            $listString .= '<a href="/aliens/mocx-mission.php?id='.$row['id'].'">[Edit]</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-5">';
            $listString .= $row['name'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-5">';
            $listString .= $mocxName;
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }
	
	public function mmStatsInfo(): array {
		
		$statsInfo['shots_taken'] = $this->shotsTaken;
		$statsInfo['shots_hit'] = $this->shotsHit;
		if($this->shotsTaken > 0) {
			$statsInfo['shots_pct'] = (round($this->shotsHit / $this->shotsTaken, 2)) * 100;
		} else {
			$statsInfo['shots_pct'] = "N/A";
		}
		
		$statsInfo['damage'] = $this->damage;
		
		$statsInfo['killed'] = $this->killed;
		$statsInfo['lost_killed'] = $this->killedLost;
		$statsInfo['total_killed'] = $this->killed + $this->killedLost;
		
		return $statsInfo;
	}
	
	public function mmSoldierInfo(): array {
        $query = "SELECT mocx.first_name, mocx.last_name, mocx.nickname
			FROM xcom_mocx as mocx 
				INNER JOIN xcom_mocx_mission as mm ON mocx.id = mm.mocx_id
			WHERE mm.id = :id";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

		$row = $queryResult->fetch();

		$mocxInfo['first_name'] = $row['first_name'];
		$mocxInfo['last_name'] = $row['last_name'];
		$mocxInfo['nickname'] = $row['nickname'];
		$mocxInfo['name'] = $row['first_name'].' "'.$row['nickname'].'" '.$row['last_name'];
		
		return $mocxInfo;
	}
		
}