<?php
declare(strict_types = 1);

namespace XCOMDatabank\Missions;

use DateTime;
use Exception;
use PDO;
use XCOMDatabank\Aliens\Chosen;
use XCOMDatabank\Forms\Field;
use XCOMDatabank\Forms\FieldCheckbox;
use XCOMDatabank\Forms\FieldFile;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldSelectMultiple;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Forms\FieldTextNumDate;
use XCOMDatabank\Management\DarkEvent;
use XCOMDatabank\Management\Info;
use XCOMDatabank\Management\Sitrep;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class Mission
{
	public ?int $id;
	public int $objectiveID;		// Mission Objective Foreign Key
	public ?int $darkEventID;		// Dark Event Foreign Key
	public ?int $chosenID;			// Chosen Foreign Key
	public array $episode; 			// YouTube Episode mission appears in. Is array (see: Shen's Last Gift in 2 episodes)
	public array $url;				// YouTube URL for episode. Is array (to match with episode #)
	public string $location; 			// Text string eg: "Slums of Chicago"
	public string $sector; 			// One of 16 map sectors, eg: "New India"
	public string $missionDate; 		// should be obvious. Should be formatted 2012/11/30 as that is how the date input returns date values
	public string $operationName; 		// e.g. Gatecrasher, Cold Monkey, whatever
	public ?array $reward; 			// Mission reward. Should be an array
	public string $difficulty; 		// eg. Easy, Medium, Difficult, Very Difficult
	public ?string $chosenResult; 		// What happened with chosen? Chosen Killed? Extracted Information? Kidnapped Soldier? None of the above? SHOULD only be set if $chosen is a valid value and not empty or null
	public string $rating; 			// eg. Fair, Good, Excellent
	public int $status; 			// 0 = failed, 1 = complete, 2 = failed but dark event countered, 3 = infiltrating
	public int $turns; 				// turns taken
	public ?string $picture;			// Mission picture
	public bool $is_chain;			// Boolean for whether mission is part of a chain or not
	public bool $is_infiltration;	// Boolean for whether mission is infiltration mission (must be 0/false if $is_chain is also false
	public ?int $infiltration;		// Infiltration %. Should be null if $is_infiltration is false
		
	function __construct() {
        $this->id = null;
		$this->objectiveID = -1;
		$this->darkEventID = null;
		$this->chosenID = null;
		$this->episode = [];
		$this->url = [];
		$this->location = "";
		$this->sector = "";
        $this->missionDate = Info::getCurrentDateShort();
		$this->operationName = "";
		$this->reward = null;
		$this->difficulty = "";
		$this->chosenResult = null;
		$this->rating = "";
		$this->status = -1;
		$this->turns = 0;
		$this->picture = null;
		$this->is_chain = false;
		$this->is_infiltration = false;
		$this->infiltration = null;
	}
		
	public function newMission(array $mission)
	{
        $this->validateMission($mission);

        $query = "INSERT INTO xcom_mission VALUES (NULL, :objectiveID, :darkEventID, :chosenID, :episode, :url, :location, 
                    :sector, :missionDate, :operationName, :reward, :difficulty, :chosenResult, :rating, :status, :turns, 
                    :picture, :ischain, :isinfiltration, :infiltration)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getMission(int $id)
	{
        $query = "SELECT * FROM xcom_mission WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

		$this->id = $id;
		$this->objectiveID = intval($row['objective_id']);
		$this->darkEventID = intval($row['dark_event_id']) ?: null;
		$this->chosenID = intval($row['chosen_id']) ?: null;
		$this->episode = explode(",",$row['episode']);
		$this->url = explode(",",$row['url']);
		$this->location = $row['location'];
		$this->sector = $row['sector'];
		$this->missionDate = $row['mission_date'];
		$this->operationName = $row['operation_name'];
		$this->reward = explode(",",$row['reward']);
		$this->difficulty = $row['difficulty'];
		$this->chosenResult = $row['chosen_result'];
		$this->rating = $row['rating'];
		$this->status = intval($row['status']);
		$this->turns = intval($row['turns']);
		$this->picture = $row['picture'];
		$this->is_chain = boolval($row['is_chain']);
		$this->is_infiltration = boolval($row['is_infiltration']);
        if($row['infiltration'] === null) {
            $this->infiltration = null;
        } else {
            $this->infiltration = intval($row['infiltration']) ?: 0;
        }

	}
		
	public function editMission(array $mission)
	{
        $this->validateMission($mission);

        $query = "UPDATE xcom_mission SET objective_id = :objectiveID, dark_event_id = :darkEventID, chosen_id = :chosenID, 
                        episode = :episode, url = :url, location = :location, sector = :sector, mission_date = :missionDate, 
                        operation_name = :operationName, reward = :reward, difficulty = :difficulty, chosen_result = :chosenResult, 
                        rating = :rating, status = :status, turns = :turns, picture = :picture, is_chain = :ischain, 
                        is_infiltration = :isinfiltration, infiltration = :infiltration WHERE id = :id";
        $params = $this->getParams();
        $params[19] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateMission(array $submit): void {
        // If a Mission ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex((int)$submit['id'], 'mission', false, "Mission ID");
        }

        // Make sure Objective ID exists and is valid
        $this->objectiveID = Validate::testIndex((int)$submit['objective_id'], 'objective', false, "Mission Objective");

        // Make sure Dark Event ID exists and is valid OR is null
        $this->darkEventID = Validate::testIndex((int)$submit['dark_event_id'] ?? null, 'dark_event', true, "Mission Dark Event");

        // Make sure Sector is Valid
        $this->sector = Validate::testArray($submit['sector'], Definitions::getSectors(), false, "Mission Sector");

        // Make sure Operation Name is a valid string
        $this->operationName = Validate::testString($submit['operation_name'], -1, -1, false, "Mission Operation Name");

        // Make sure Difficulty is valid value
        $this->difficulty = Validate::testArray($submit['difficulty'], Definitions::getDifficulty(), false, "Mission Difficulty");

        // Make sure Status ID exists and is valid
        $this->status = Validate::testIndex((int)$submit['mission_status'], 'mission_status', false, "Mission Status");

        // Test to make sure Is Chain is set
        $this->is_chain = Validate::testTF($submit['is_chain'] ?? false, false, "Is Mission Part of a Chain?");

        // Make sure Rating is a valid value
        $this->rating = Validate::testArray($submit['rating'], Definitions::getRating(), false, "Mission Rating");

        // Make sure Mission Date is set
        $this->missionDate = Validate::testDate($submit['mission_date'] ?? $this->missionDate, false, "Mission Date");

        // Make sure Location is set
        $this->location = Validate::testString($submit['location'] ?? $this->sector, -1, -1, false, "Mission Location");

        // Make sure Turns is set
        $this->turns = Validate::testInteger(intval($submit['turns']) ?? 1, 1, 100, false, "Mission Turns");

        // Make sure Chosen ID exists and is valid OR is null
        $this->chosenID = Validate::testIndex(intval($submit['chosen_id']) ?? null, "chosen", true, "Mission Chosen");

        // Check Chosen Result, but only if Chosen ID Exists
        if(!empty($this->chosenID)) {
            $this->chosenResult = Validate::testArray($submit['chosen_result'], Definitions::$chosenResult, false, "Mission Chosen Result");
        } else {
            $this->chosenResult = null;
        }

        // Test to make sure is_infiltration is valid
        $this->is_infiltration = Validate::TestTF($submit['is_infiltration'] ?? false, false, "Is Mission Infiltration?");

        // Test Infiltration, but only if is_infiltration is true
        if($this->is_infiltration) {
            $this->infiltration = Validate::TestInteger((int)$submit['infiltration'], 0, 250, false, "Infiltration Percent");
        } else {
            $this->infiltration = null;
        }

        // Test Episode Array
        $episodeList = explode(",",$submit['episode']) ?? array();
        foreach($episodeList as $key => $value) {
            $this->episode[$key] = Validate::testInteger(intval($value), 1, 150, false, "Mission Episode Number");
        }

        // Test URL Array
        $urlList = explode(",",$submit['url']) ?? array();
        foreach($urlList as $key => $value) {
            $this->url[$key] = Validate::testURL(trim($value), false, "Mission Episode URL");
        }

        // Test Rewards
        $rewardList = $submit['reward'] ?? array();
        foreach($rewardList as $key => $value) {
            $this->reward[$key] = Validate::testString($value, -1, -1, true, "Mission Reward");
        }

        // Picture Handling
        /* There are Five cases we need to deal with:
            1) There is no current picture (e.g. empty($this->picture) is true) and a New picture is being added. This
                should always be the case on mission completion
            2) A picture is set, Deleted is set, but a new picture is not set. This should result in $this->picture being cleared
            3) A picture is set, Deleted is set, and a new picture is being uploaded. Should replace $this->picture with new picture
            4) A picture is set, a new picture is selected, but Deleted IS NOT set. New picture should be ignored
            5) There is no current picture and no new image is being added - keep $this->picture empty
        */
        if(empty($submit['picture_current'])) { // If picture_current isn't set, assume no picture is attached. First step for cases 1 and 5 above
            if(!empty($submit['picture'])) { // If picture is being submitted, do step 1. Else, do nothing
                $this->picture = Validate::testImage($submit['picture'], 'mission', $this->operationName, true, "Mission Image");
            } else {
                $this->picture = null;
            }
        } else { // current_picture IS set. First step for cases 2, 3, and 4 above
            if(!empty($submit['delete'])) { // If Deleted tag is set, prepare to do either steps 2 or 3. Else, do nothing
                if(empty($submit['picture'])) { // If no new image is being uploaded, set $this->picture to null
                    $this->picture = null;
                } else { // Otherwise, Do Case 3
                    $this->picture = Validate::testImage($submit['picture'], 'mission', $this->operationName."-".$submit['id'], true, "Mission Image");
                }
            }
        }
    }

    public function processForm(array $submit, string $redirect = ''): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editMission($submit);
            } else {
                return Error::returnError("Mission ID is set, but Mission ID is not numeric");
            }
        } else {
            $this->newMission($submit);
        }
        if(!empty($redirect)) {
            header('Location: '.$redirect);
        }
        return "";
    }

    private function getParams(): array {
        $params[0] = array("param" => ":objectiveID", "var" => $this->objectiveID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":episode", "var" => implode(',',$this->episode), "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":url", "var" => implode(',',$this->url), "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":location", "var" => $this->location, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":sector", "var" => $this->sector, "type" => PDO::PARAM_STR,);
        $params[5] = array("param" => ":missionDate", "var" => $this->missionDate, "type" => PDO::PARAM_STR,);
        $params[6] = array("param" => ":operationName", "var" => $this->operationName, "type" => PDO::PARAM_STR,);
        $params[7] = array("param" => ":difficulty", "var" => $this->difficulty, "type" => PDO::PARAM_STR,);
        $params[8] = array("param" => ":rating", "var" => $this->rating, "type" => PDO::PARAM_STR,);
        $params[9] = array("param" => ":status", "var" => $this->status, "type" => PDO::PARAM_INT,);
        $params[10] = array("param" => ":turns", "var" => $this->turns, "type" => PDO::PARAM_INT,);
        $params[11] = array("param" => ":ischain", "var" => $this->is_chain, "type" => PDO::PARAM_BOOL,);
        $params[12] = array("param" => ":isinfiltration", "var" => $this->is_infiltration, "type" => PDO::PARAM_BOOL,);
        $params[13] = array("param" => ":infiltration", "var" => $this->infiltration, "type" => PDO::PARAM_INT,);
        $params[14] = array("param" => ":darkEventID", "var" => $this->darkEventID, "type" => PDO::PARAM_INT,);
        $params[15] = array("param" => ":chosenID", "var" => $this->chosenID, "type" => PDO::PARAM_INT,);
        $params[16] = array("param" => ":reward", "var" => implode(',',$this->reward), "type" => PDO::PARAM_STR,);
        $params[17] = array("param" => ":chosenResult", "var" => $this->chosenResult, "type" => PDO::PARAM_STR,);
        $params[18] = array("param" => ":picture", "var" => $this->picture, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public static function getMissionForm(Mission $mission)
    {
        $id = $mission->id;
        if ($id != "" and $id !== null and is_numeric($id)) {
            echo FieldHidden::getField('id', strval($id));
        }

        echo FieldText::getField('operation_name', $mission->operationName, 'form-control', 'operation-name',
            'Operation Name', array('col-md-4', 'pb-2', 'form-floating'), true);

        echo FieldTextNumDate::getField('mission_date', $mission->missionDate, 'form-control', 'operation-name',
            'Mission Date', array('col-md-2', 'pb-2', 'form-floating'), true, false,'2035-02-28', '2037-12-31');

        echo FieldTextNum::getField('turns', strval($mission->turns), 'form-control', 'turns',
            'Turns', array('col-md-1', 'pb-2', 'form-floating'), false, false,'1', '100');

        echo FieldSelect::getField('is_chain', $mission->is_chain ? '1' : '0', 'form-control', 'is-chain',
            'Chain?', array('col-md-1', 'pb-2', 'form-floating'), true, Definitions::arrayYesNo());

        echo FieldSelect::getField('is_infiltration', $mission->is_infiltration ? '1' : '0', 'form-control', 'is-infiltration',
            'Infil?', array('col-md-1', 'pb-2', 'form-floating'), true, Definitions::arrayYesNo());

        echo FieldTextNum::getField('infiltration', strval($mission->infiltration), 'form-control', 'infiltration',
            'Infiltration %', array('col-md-1', 'pb-2', 'form-floating'), false, false,'0', '250');

        echo FieldSelect::getField('rating', $mission->rating, 'form-control', 'rating',
            'Rating', array('col-md-2', 'pb-2', 'form-floating'), true, Definitions::getRating());


        echo FieldSelect::getField('mission_type', strval(Objective::getMissionTypeByObjective($mission->objectiveID)), 'form-control mission-type', 'mission-type',
            'Mission Type', array('col-md-4', 'pb-2', 'form-floating'), true, MissionType::getMissionTypeListArray());

        echo FieldSelect::getField('objective_id', strval($mission->objectiveID), 'form-control objective', 'objective',
            'Mission Objective', array('col-md-5', 'pb-2', 'form-floating'), true, Objective::getObjectiveList(Objective::getMissionTypeByObjective($mission->objectiveID)));

        echo FieldSelect::getField('mission_status', strval($mission->status), 'form-control', 'status',
            'Mission Status', array('col-md-3', 'pb-2', 'form-floating'), true, MissionStatus::getMissionStatusArray());

        echo FieldText::getField('location', $mission->location, 'form-control', 'location',
            'Location', array('col-md-4', 'pb-2', 'form-floating'), true);

        echo FieldSelect::getField('sector', $mission->sector, 'form-control', 'sector',
            'Sector', array('col-md-3', 'pb-2', 'form-floating'), true, Definitions::getSectors());

        echo FieldSelect::getField('difficulty', $mission->difficulty, 'form-control', 'difficulty',
            'Difficulty', array('col-md-2', 'pb-2', 'form-floating'), true, Definitions::getDifficulty());

        echo FieldSelect::getField('dark_event_id', strval($mission->darkEventID), 'form-control', 'dark-event',
            'Dark Event', array('col-md-3', 'pb-2', 'form-floating'), false, DarkEvent::getDarkEventArray());

        echo FieldSelectMultiple::getField('sitrep[]', Sitrep::getSitrepArrayByMission($mission->id), 'form-control', 'sitrep',
            'SITREPs', array('col-md-3', 'pb-2', 'form-floating'), false, Sitrep::getSitrepArray());

        echo FieldText::getField('episode', join(",", $mission->episode), 'form-control', 'episode',
            'Episode #(s)', array('col-md-2', 'pb-2', 'form-floating'), true);

        echo FieldText::getField('url', join(",", $mission->url), 'form-control', 'url',
            'Episode URL(s)', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldSelect::getField('chosen_id', strval($mission->chosenID), 'form-control', 'chosen',
            'Chosen', array('col-md-2', 'pb-2', 'form-floating'), false, Chosen::getChosenArray());

        echo FieldSelect::getField('chosen_result', strval($mission->chosenResult), 'form-control', 'chosen-result',
            'Chosen Result', array('col-md-2', 'pb-2', 'form-floating'), false, Definitions::getChosenResult());
    }



    public static function getMissionRewards(string $reward, bool $lastElement): void
    {
        echo FieldText::getField('reward[]', $reward, 'form-control', 'reward',
            'Reward', array('col-md-11', 'pb-2', 'form-floating'), false);

        echo Field::repeatButton($lastElement);
    }

    public static function getMissionPicture(?string $picture): void {
        if($picture != null and $picture != "") {
            echo '<img src="https://xcom-databank.games/'.$picture.'" alt="Picture" width="600" />';
            echo FieldHidden::getField('picture_current', $picture);
        }

        echo FieldFile::getField('picture', '', 'mission-picture', 'form-control', false, '.png, .jpg, .jpeg, .webp',
                            'Image Upload', array('col-md-6','mb-3'));

        $infoArray = array(
            array(
                'value' => '1'
            ),
        );
        echo FieldCheckbox::getField('delete', '', 'checkbox', $infoArray, 'Delete Current?', array('col-md-2','mb-3'));
    }

    public static function getListPage(): void {
        $query = "SELECT mission.id, mission.operation_name, mission.mission_date, mission.difficulty, mission.rating, 
            objective.description as objective, type.description as mission_type 
			FROM xcom_mission as mission 
				INNER JOIN xcom_objective as objective ON mission.objective_id = objective.id 
				INNER JOIN xcom_mission_type as type ON objective.mission_type_id = type.id 
			ORDER BY mission.mission_date DESC, mission.episode DESC, mission.id DESC";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table mission-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-2">Date</div>'."\n";
        $listString .= '<div class="col-7">Mission</div>'."\n";
        $listString .= '<div class="col-3">Mission Info</div>'."\n";
        $listString .= '</div>'."\n";


        while ($row = $queryResult->fetch()) {
            if ($row['rating'] == "Flawless") {
                $rowClass = "row-green";
            } elseif ($row['rating'] == "Excellent") {
                $rowClass = "row-teal";
            } elseif ($row['rating'] == "Good") {
                $rowClass = "row-blue";
            } elseif ($row['rating'] == "Fair") {
                $rowClass = "row-orange";
            } elseif ($row['rating'] == "Poor") {
                $rowClass = "row-red";
            } else {
                $rowClass = "row-yellow";
            }

            // Convert Date to better format
            try {
                $dateTime = new DateTime($row['mission_date']);
            } catch (Exception $e) {
                echo $e;
                exit();
            }
            $newDate = $dateTime->format('F j, Y');

            $listString .= '<div class="row '.$rowClass.'">'."\n";
            $listString .= '<div class="col-2">';
            $listString .= $newDate.'<br />'."\n";
            $listString .= '<a href="/mission/mission-only.php?id='.$row['id'].'">[Edit]</a>'."\n";
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-7">';
            $listString .= '<strong>'.$row['operation_name'].'</strong><br />'."\n";
            $listString .= $row['mission_type'].' - '.$row['objective']."\n";
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $row['difficulty'].'<br />'."\n";
            $listString .= $row['rating']."\n";
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }


	public function missionDarkEvent(): string {
		if($this->darkEventID !== null) {
            $query = "SELECT name FROM xcom_dark_event WHERE id = :id";
            $params[0] = array("param" => ":id", "var" => $this->darkEventID, "type" => PDO::PARAM_INT,);

            $queryResult = Database::runQuery('select', $query, $params);

			$row = $queryResult->fetch();
			
			return $row['name'];
		} else {
			return "";
		}
	}
	
	public function missionSitreps(): array {
        $query = "SELECT sitrep.name 
			FROM xcom_sitrep as sitrep 
				INNER JOIN xcom_mission_sitrep as ms ON sitrep.id = ms.sitrep_id
				INNER JOIN xcom_mission as mission ON ms.mission_id = mission.id
			WHERE mission.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
		
		$sitreps = [];
		while ($row = $queryResult->fetch()) {
			array_push($sitreps, $row['name']);
		}
		
		return $sitreps;
	}
	
	public function missionInfo(): array {
        $query = "SELECT type.description as type, objective.description as objective
			FROM xcom_mission_type as type 
				INNER JOIN xcom_objective as objective ON objective.mission_type_id = type.id
				INNER JOIN xcom_mission as mission ON mission.objective_id = objective.id
			WHERE mission.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

		$row = $queryResult->fetch();
		$missionType['type'] = $row['type'];
		$missionType['objective'] = $row['objective'];
		
		return $missionType;
	}

    public static function getOperationName($id): string {
        $query = "SELECT operation_name FROM xcom_mission WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        $row = $queryResult->fetch();

        return $row['operation_name'];
    }

    public static function getMissionArray($header = false): array {
        $query = "SELECT * FROM xcom_mission WHERE status < 4 ORDER BY id DESC";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        if($header) {
            $typeArray = array(
                array(
                    'text' => "N/A",
                    'value' => '',
                ),
            );
        } else {
            $typeArray = array();
        }

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['operation_name'],
                'value' => $item['id'],
            );
            array_push($typeArray, $newArray);
        }

        return $typeArray;
    }

    public static function getMissionArrayInfil($header = false): array {
        $query = "SELECT * FROM xcom_mission WHERE is_infiltration = true ORDER BY id DESC";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        if($header) {
            $typeArray = array(
                array(
                    'text' => "N/A",
                    'value' => '',
                ),
            );
        } else {
            $typeArray = array();
        }

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['operation_name'],
                'value' => $item['id'],
            );
            array_push($typeArray, $newArray);
        }

        return $typeArray;
    }
}