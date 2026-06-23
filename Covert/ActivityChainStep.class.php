<?php
declare(strict_types = 1);

namespace XCOMDatabank\Covert;

use PDO;
use XCOMDatabank\Forms\Field;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Missions\Mission;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class ActivityChainStep
{
	public ?int $id;
	public int $chain;					// ID of the Activity Chain this is a part of, from xcom_activity_chain_campaign
	public int $step;					// Which step in the chain this activity is
	public string $type;					// Activity Type (Mission or Covert)
	public ?int $mission;				// ID from a mission from xcom_mission if $type = mission, otherwise null
	public ?int $covert;					// ID from a covert activity from xcom_covert_activity if $type = covert, otherwise null
	public string $status;					// Status of this step of the Activity.
		
	function __construct() {
		// Set Default Values
        $this->id = null;
		$this->chain = -1;
		$this->step = 0;
		$this->type = "";
		$this->mission = null;
		$this->covert = null;
		$this->status = "";
	}
		
	public function newActivityChainStep($activityChainStep) {
        $this->validateActivityChainStep($activityChainStep);

        $query = "INSERT INTO xcom_activity_chain_steps VALUES (NULL, :chain, :step, :type, :mission, :covert, :status)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
			
	public function getActivityChainStep(int $id) {
        $query = "SELECT * FROM xcom_activity_chain_steps WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->chain = intval($row['activity_chain']);
		$this->step = intval($row['step']);
		$this->type = $row['type'];
		$this->mission = intval($row['mission']) ?: null;
		$this->covert = intval($row['covert']) ?: null;
		$this->status = $row['status'];
	}
		
	public function editActivityChainStep($activityChainStep) {
        $this->validateActivityChainStep($activityChainStep);

        $query = "UPDATE xcom_activity_chain_steps SET activity_chain = :chain, step = :step, type = :type, mission = :mission, 
                        covert = :covert, status = :status WHERE id = :id";
        $params = $this->getParams();
        $params[6] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateActivityChainStep(array $submit): void {
        // If an Activity Chain Step ID was submitted, make sure it is valid
        if(isset($submit['step_id'])) {
            $this->id = Validate::testIndex($submit['step_id'], 'activity_chain_steps', false, "Activity Chain Step ID");
        }

        // Activity Chain
        $this->chain = Validate::testIndex($submit['chain'], 'activity_chain', false, "Activity Chain Step Chain");

        // Step
        $this->step = Validate::testInteger(intval($submit['step']), 1, 8, false, "Activity Chain Step Number");

        // Type
        $this->type = Validate::testArray($submit['step_type'], Definitions::getActivityChainType(), false, "Activity Chain Step Type");

        // Mission ID
        $this->mission = Validate::testIndex(intval($submit['step_mission']) ?? null, 'mission', true, "Activity Chain Step Mission");

        // Covert ID
        $this->covert = Validate::testIndex(intval($submit['step_covert']) ?? null, 'covert_action', true, "Activity Chain Step Covert Action");

        // Type
        $this->status = Validate::testArray($submit['step_status'], Definitions::getActivityTypeStatus(), false, "Activity Chain Step Status");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":chain", "var" => $this->chain, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":step", "var" => $this->step, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":type", "var" => $this->type, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":mission", "var" => $this->mission ?? null, "type" => PDO::PARAM_INT,);
        $params[4] = array("param" => ":covert", "var" => $this->covert ?? null, "type" => PDO::PARAM_INT,);
        $params[5] = array("param" => ":status", "var" => $this->status, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect = ''): string {
        if(isset($submit['step_id'])) {
            if(is_numeric($submit['step_id'])) {
                $this->editActivityChainStep($submit);
            } else {
                return Error::returnError("Covert Chain Step ID is set, but Covert Chain Step ID is not numeric");
            }
        } else {
            $this->newActivityChainStep($submit);
        }
        //header('Location: '.$redirect);
        return "";
    }

    public static function getActivityChainStepForm(ActivityChainStep $step) {
        echo FieldTextNum::getField('step[]', strval($step->step), 'form-control', 'step',
            "Chain Step", array('col-md-2', 'pb-2', 'pe-3', 'form-floating'), true, false,'1', '8');

        echo FieldSelect::GetField('step_type[]', $step->type, 'form-control', 'type',
            'Step Type', array('col-md-2','pb-2', 'pe-3','form-floating'), true, Definitions::getActivityChainType());

        echo FieldSelect::GetField('step_mission[]', strval($step->mission) ?? null, 'form-control', 'mission',
            'Mission', array('col-md-3','pb-2', 'pe-3','form-floating'), false, self::availableMissions($step));

        echo FieldSelect::GetField('step_covert[]', strval($step->covert) ?? null, 'form-control', 'covert',
            'Covert Action', array('col-md-3', 'pe-3','pb-2','form-floating'), false, self::availableActivities($step));

        echo FieldSelect::GetField('step_status[]', $step->status, 'form-control', 'status',
            'Status', array('col-md-2','pb-2', 'pe-3','form-floating'), true, Definitions::getActivityTypeStatus());

        echo Field::repeatButton(true);
    }
	
	// Get List of Missions Available to be Added as a step
	// That is to say, missions that:
	//		1) Are marked as being part of a chain
	//		2) Are not already part of a chain
	public static function availableMissions(ActivityChainStep $step): array {
		$query = "SELECT * FROM xcom_mission 
                    WHERE is_chain = :ischain AND 
                    id NOT IN (SELECT mission FROM xcom_activity_chain_steps WHERE mission IS NOT NULL)";
        $params[0] = array("param" => ":ischain", "var" => true, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

		$missionArray = array(
            array(
                'text' => "N/A",
                'value' => '',
            )
        );
		// First, let's add any mission which is actually attached, so it will show up on the form
		if(!empty($step->mission)) {
			$getMission = new Mission;
			$getMission->getMission($step->mission);
            $missionList = array(
                'text' => $getMission->operationName,
                'value' => $getMission->id,
            );
			array_push($missionArray, $missionList);
		}
		
		// Find available missions
		while ($row = $queryResult->fetch()) {
			$getMission = new Mission;
			$getMission->getMission(intval($row['id']));
            $missionList = array(
                'text' => $getMission->operationName,
                'value' => $getMission->id,
            );
			array_push($missionArray, $missionList);
		}
		
		return $missionArray;
	}
	
	// Get List of Covert Activities Available to be Added as a step
	// That is to say, activities that:
	//		1) Are marked as being part of a chain
	//		2) Are not already part of a chain
	public static function availableActivities(ActivityChainStep $step): array {
        $query = "SELECT * FROM xcom_covert_action 
                    WHERE is_chain = :ischain AND 
                    id NOT IN (SELECT covert FROM xcom_activity_chain_steps WHERE covert IS NOT NULL)";
        $params[0] = array("param" => ":ischain", "var" => true, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

		$covertArray = array(
            array(
                'text' => "N/A",
                'value' => '',
            )
        );
		//First, Let's add any covert activity that is already attached to this step, so it will show up on the form
		if($step->covert != "" and $step->covert != null) {
			$getCovert = new CovertAction;
			$getCovert->getCovertAction($step->covert);
            $covertList = array(
                'text' => $getCovert->getCovertType(),
                'value' => $getCovert->id,
            );
			array_push($covertArray, $covertList);
		}
		
		// Then add available covert activities
		while ($row = $queryResult->fetch()) {
			$getCovert = new CovertAction;
			$getCovert->getCovertAction((int)$row['id']);
            $covertList = array(
                'text' => $getCovert->getCovertType(),
                'value' => $getCovert->id,
            );
			array_push($covertArray, $covertList);
		}
		
		return $covertArray;
	}
}