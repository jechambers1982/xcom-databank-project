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

class MissionType
{
	public ?int $id;
	public string $description;
	public bool $enabled;
	public ?string $notes;
		
	function __construct() {
        $this->id = null;
		$this->description = "";
		$this->enabled = true;
		$this->notes = null;
	}
		
	public function newMissionType(array $missionType) : void {
        $this->validateMissionType($missionType);
			
        $query = "INSERT INTO xcom_mission_type VALUES (NULL, :description, :enabled, :notes)";
        $params = $this->getParams();
			
        $queryResult = Database::runQuery('insert', $query, $params);
				
        $this->id = $queryResult;
	}
		
	public function getMissionType(int $id) : void {
		
		$query = "SELECT * FROM xcom_mission_type WHERE id = :id";
		$params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);
		
		$queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->description = $row['description'];
		$this->enabled = boolval($row['enabled']);
		$this->notes = $row['notes'];
	}
		
	public function editMissionType(array $missionType) : void {
        $this->validateMissionType($missionType);
			
        $query = "UPDATE xcom_mission_type SET description = :description, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[3] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);
			
        Database::runQuery('update', $query, $params);
	}
	
	public function processForm(array $submit, string $redirect): string {
		if(isset($submit['id'])) {
			if(is_numeric($submit['id'])) {
				$this->editMissionType($submit);
			} else {
                return Error::returnError("Mission Type ID is set, but Mission Type ID is not numeric");
			}
		} else {
			$this->newMissionType($submit);
		}
		header('Location: '.$redirect);
        return "";
	}
	
	public static function getObjectiveIdByMissionType(int $missionType) : int {
	    // This function only really works if a mission type only has one objective type.
	    // Otherwise, it will just return the first objective returned
	    
	    $query = "SELECT id FROM xcom_objective WHERE mission_type_id = :id and enabled = true";
	    $params[0] = array("param" => ":id", "var" => $missionType, "type" => PDO::PARAM_INT,);
	    
	    $queryResult = Database::runQuery('select', $query, $params);

        if(!empty($queryResult)) {
            $row = $queryResult->fetch();
        } else {
            $row['id'] = -1;
        }
	    
	    return (int)$row['id'];
	}
	
	public static function getMissionTypeListArray(): array {
		$typeArray = array(
            array(
                'text' => 'Select Mission Type',
                'value' => "",
            ),
        );
		
		$query = "SELECT * FROM xcom_mission_type WHERE enabled = true ORDER BY description";
		$params = array();
		
		$queryResult = Database::runQuery('select', $query, $params);
		
		while ($row = $queryResult->fetch()) {
            // Would like to do some check to see if missions are even available to selected
            $gatecrasherID = self::getMissionTypeIdByDescription('Gatecrasher'); // Gatecrasher ID
            $blacksiteID = self::getMissionTypeIdByDescription('ADVENT Blacksite'); // Blacksite ID
            $forgeID = self::getMissionTypeIdByDescription('Blacksite Data Coordinates'); // ADVENT Forge
            $gatewayID = self::getMissionTypeIdByDescription('Codex Brain Coordinates'); // Gateway Mission
            $towerID = self::getMissionTypeIdByDescription('ADVENT Network Tower'); // ADVENT Network Tower
            $assassinID = self::getMissionTypeIdByDescription('Chosen Assassin Stronghold'); // Assassin Stronghold
            $hunterID = self::getMissionTypeIdByDescription('Chosen Hunter Stronghold'); // Hunter Stronghold
            $warlockID = self::getMissionTypeIdByDescription('Chosen Warlock Stronghold'); // Warlock Stronghold

            // If gatecrasher hasn't been completed, only Gatecrasher should be listed, otherwise, everything but Gatecrasher is still allowable
            if(self::isMissionComplete($gatecrasherID)) {
                // Long series of checks for if missions have been completed
                if(
                    ( $row['description'] == 'ADVENT Blacksite'
                        AND !self::isMissionComplete($blacksiteID)
                    ) OR // If the Blacksite has not been completed yet, list it

                    ( $row['description'] == 'Blacksite Data Coordinates'
                        AND self::isMissionComplete($blacksiteID)
                        AND !self::isMissionComplete($forgeID)
                    ) OR // If Blacksite is done but Forge is not, list it
						
                    ( $row['description'] == 'Codex Brain Coordinates'
                        AND !self::isMissionComplete($gatewayID)
                    ) OR // If Gateway mission is not done, list it (because, amazingly, you can do it before the Blacksite if you really wanted to
						
                    ( $row['description'] == 'ADVENT Network Tower'
                        AND self::isMissionComplete($forgeID)
                        AND self::isMissionComplete($gatewayID)
                        AND !self::isMissionComplete($towerID)
                    ) OR // If Forge is done, Gateway is done, but Network Tower is not done, list it
						
                    ( $row['description'] == 'Alien Fortress'
                        AND self::isMissionComplete($towerID)
                    ) OR // If Network Tower is done, list Leviathan
						
                    ( $row['description'] == 'Chosen Assassin Stronghold'
                        AND !self::isMissionComplete($assassinID)
                    ) OR // If Assassin Stronghold is not done, list it
						
                    ( $row['description'] == 'Chosen Hunter Stronghold'
                        AND !self::isMissionComplete($hunterID)
                    ) OR // If Hunter Stronghold is not done, list it
						
                    ( $row['description'] == 'Chosen Warlock Stronghold'
                        AND !self::isMissionComplete($warlockID)
                    ) OR // If Warlock Stronghold is not done, list it
						
                    ( $row['description'] == 'Avenger Assault'
                        AND ( !self::isMissionComplete($assassinID)
                            OR !self::isMissionComplete($hunterID)
                            OR !self::isMissionComplete($warlockID)
                        )
                    ) OR // If any of the Chosen strongholds aren't done yet, allow Avenger Assaults
						
                    ( $row['description'] != 'ADVENT Blacksite'
                        AND $row['description'] != 'Blacksite Data Coordinates'
                        AND $row['description'] != 'Codex Brain Coordinates'
                        AND $row['description'] != 'ADVENT Network Tower'
                        AND $row['description'] != 'Alien Fortress'
                        AND $row['description'] != 'Chosen Assassin Stronghold'
                        AND $row['description'] != 'Chosen Hunter Stronghold'
                        AND $row['description'] != 'Chosen Warlock Stronghold'
                        AND $row['description'] != 'Avenger Assault'
                        AND !self::isMissionComplete($towerID)
                    ) // If not any of the before mentioned missions and the Network Tower isn't done, then list
                )
                {
                    $printArray = array(
                        'text' => $row['description'],
                        'value' => $row['id'],
                    );
                    array_push($typeArray, $printArray);
                }
			} elseif($row['description'] == 'Gatecrasher') {
                $printArray = array(
                    'text' => $row['description'],
                    'value' => $row['id'],
                );
                array_push($typeArray, $printArray);
            }
		}
		return $typeArray;
	}

    public static function getMissionTypeArray(): array {
        $query = "SELECT * FROM xcom_mission_type ORDER BY description";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $typeArray = array(
            array(
                'text' => "Select Mission Type",
                'value' => "",
            ),
        );

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['description'],
                'value' => $item['id'],
            );
            array_push($typeArray, $newArray);
        }

        return $typeArray;
    }
	
	public static function getMissionTypeForm(MissionType $missionType) : void {
	    $id = $missionType->id;
	    if($id != "" and $id !== null and is_numeric($id)) {
	        echo FieldHidden::getField('id',strval($id));
	    }
	    
	    // Print Mission Type Description Form Field
	    echo FieldText::getField('description', $missionType->description, 'form-control', 'description',
	        'Description', array('col-md-3', 'pb-2', 'form-floating'), true);

        // Print Mission Types Notes Field
        echo FieldText::getField('notes', $missionType->notes, 'form-control', 'notes',
            'Mission Type Notes', array('col-md-7','pb-2','form-floating'), false);

        echo FieldSelect::getField('enabled', strval($missionType->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-2','pb-2','form-floating'), true, Definitions::arrayYesNo());
	}

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_mission_type ORDER BY enabled DESC, description";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table mission-type-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Mission Type</div>'."\n";
        $listString .= '<div class="col-2">Enabled</div>'."\n";
        $listString .= '<div class="col-6">Notes</div>'."\n";
        $listString .= '</div>'."\n";

		foreach ($queryResult as $row) {

			if($row['enabled']) {
				$rowClass = "row-green";
			} else {
				$rowClass = "row-red";
			}

            $listString .= '<div class="row '.$rowClass.'">'."\n";

            $listString .= '<div class="col-4">';
            $listString .= '<a href="/mission/mission-type.php?id='.$row['id'].'">'.$row['description'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';

            if($row['enabled']) {
                $listString .= "Enabled";
            } else {
                $listString .=  "Disabled";
            }

            $listString .= '</div>'."\n";
            $listString .= '<div class="col-6">';
            $listString .= $row['notes'];
			$listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

		}
        $listString .= '</div>'."\n";

        echo $listString;
    }
	
	private function validateMissionType(array $submit) : void {
	    // If a Mission Type ID was submitted, make sure it is valid
	    if(isset($submit['id'])) {
	        $this->id = Validate::testIndex($submit['id'], 'mission_type', false, "Mission Type ID");
	    }
	    
	    // Make sure description is a valid, non-null string
	    $this->description = Validate::testString($submit['description'], -1, -1, false, "Mission Type Description");
	    
	    // Make sure enabled is some form of boolean value
	    $this->enabled = Validate::testTF($submit['enabled'], false, "Mission Type Enabled");
	    
	    // Make sure notes is a valid string or null
	    $this->notes = Validate::testString($submit['notes'] ?? null, -1, -1, true, "Mission Type Description");
	}
	
	private static function getMissionTypeIdByDescription(string $description) : int {
	    $query = "SELECT id FROM xcom_mission_type WHERE description = :description";
	    $params[0] = array("param" => ":description", "var" => $description, "type" => PDO::PARAM_STR,);
		
		$queryResult = Database::runQuery('select', $query, $params);
		
		$row = $queryResult->fetch();

		if($row != false) {
			return intval($row['id']);
		} else {
			return 0;
		}
	}
	
	private static function isMissionComplete(int $missionID) : bool {
		$objectiveID = self::getObjectiveIdByMissionType($missionID);

        $query = "SELECT mission.id FROM xcom_mission as mission
                    INNER JOIN xcom_mission_status as status ON mission.status = status.id
                    WHERE status.name = :status and objective_id = :objective";
        $params[0] = array("param" => ":status", "var" => "Completed", "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":objective", "var" => $objectiveID, "type" => PDO::PARAM_INT,);
		
		$queryResult = Database::runQuery('select', $query, $params);
		
		$missionComplete = $queryResult->fetch();
		if($missionComplete) {
			return true;
		} else {
			return false;
		}
	}

    private function getParams(): array {
        $params[0] = array("param" => ":description", "var" => $this->description, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[2] = array("param" => ":notes", "var" => $this->notes, "type" => PDO::PARAM_STR,);
        return $params;
    }
}