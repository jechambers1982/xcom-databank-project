<?php
declare(strict_types = 1);

namespace XCOMDatabank\Covert;

use PDO;
use XCOMDatabank\Forms\Field;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Soldiers\Soldier;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;
	
class CovertOperative
{
	public ?int $id;
	public int $actionID;
	public ?int $soldierID;
	public ?string $opResource;
	public string $requirement;
	public ?string $reward;
	public bool $promoted;
	public ?string $status;
		
	function __construct() {
        $this->id = null;
		$this->actionID = -1;
		$this->soldierID = null;
		$this->opResource = null;
		$this->requirement = "";
		$this->reward = null;
		$this->promoted = false;
		$this->status = null;
	}
		
	public function newCovertOperative($covertOperative) {
        $this->validateCovertOperative($covertOperative);

        $query = "INSERT INTO xcom_covert_operative VALUES (NULL, :actionID, :soldierID, :resource, :requirement, :reward, :promoted, :status)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getCovertOperative(int $id) {
        $query = "SELECT * FROM xcom_covert_operative WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->actionID = intval($row['action_id']);
		$this->soldierID = intval($row['soldier_id']);
		$this->opResource = $row['resource'];
		$this->requirement = $row['requirement'];
		$this->reward = $row['reward'];
		$this->promoted = boolval($row['promoted']);
		$this->status = $row['status'];
	}
		
	public function editCovertOperative($covertOperative) {
        $this->validateCovertOperative($covertOperative);

        $query = "UPDATE xcom_covert_operative SET action_id = :actionID, soldier_id = :soldierID, resource = :resource, 
                        requirement = :requirement, reward = :reward, promoted = :promoted, status = :status WHERE id = :id";
        $params = $this->getParams();
        $params[8] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateCovertOperative(array $submit): void {
        // If a Covert Operative ID was submitted, make sure it is valid
        if(isset($submit['operative_id'])) {
            $this->id = Validate::testIndex($submit['operative_id'], 'covert_operative', false, "Covert Operative ID");
        }

        // Covert Action ID
        $this->actionID = Validate::testIndex($submit['action_id'], 'covert_action', false, "Covert Operative Action ID");

        // soldier ID
        $this->soldierID = Validate::testIndex($submit['soldier_id'], 'soldier', true, "Covert Operative Soldier ID");

        // Resource
        $this->opResource = Validate::testString($submit['resource'], -1, -1, true, "Covert Operative Resource");

        // Requirement
        $this->requirement = Validate::testArray($submit['requirement'], Definitions::getOperativeRequirements(), false, "Covert Operative Requirement");

        // Reward
        $this->reward = Validate::testString($submit['opReward'], -1, -1, true, "Covert Operative Reward");

        // Promoted
        $this->promoted = Validate::testTF($submit['promoted'], false, "Covert Operative Promoted");

        // Status
        $this->status = Validate::testArray($submit['opStatus'], Definitions::getOperativeStatus(), true, "Covert Operative Status");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":actionID", "var" => $this->actionID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":soldierID", "var" => $this->soldierID ?? null, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":resource", "var" => $this->opResource ?? null, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":requirement", "var" => $this->requirement, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":reward", "var" => $this->reward ?? null, "type" => PDO::PARAM_STR,);
        $params[5] = array("param" => ":promoted", "var" => $this->promoted, "type" => PDO::PARAM_BOOL,);
        $params[6] = array("param" => ":status", "var" => $this->status ?? null, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit): ?string {
        if(isset($submit['operative_id'])) {
            if(is_numeric($submit['operative_id'])) {
                $this->editCovertOperative($submit);
            } else {
                return Error::returnError("Covert Operative ID is set, but Covert Operative ID is not numeric");
            }
        } else {
            $this->newCovertOperative($submit);
        }
        return null;
    }

    public static function getCovertOperativeForm(CovertOperative $operative, bool $multi = true, bool $lastElement = true) {
        $nameA = "";
        if($multi) {
            $nameA = "[]";
        }

        if(!empty($operative->id) and is_numeric($operative->id)) {
            echo FieldHidden::getField('operative_id'.$nameA,strval($operative->id));
        }

        echo FieldSelect::GetField('soldier_id'.$nameA, strval($operative->soldierID), 'form-control', 'soldier-id',
            'Soldier', array('col-md-4','pb-2','form-floating'), false, Soldier::getAvailableSoldierArray(true));

        echo FieldText::getField('resource'.$nameA, $operative->opResource, 'form-control', 'resource',
            'Resource', array('col-md-4', 'pb-2', 'form-floating'), false);

        echo FieldSelect::GetField('requirement'.$nameA, $operative->requirement, 'form-control', 'requirement',
            'Requirement', array('col-md-4','pb-2','form-floating'), true, Definitions::getOperativeRequirements());


        echo FieldText::getField('opReward'.$nameA, $operative->reward, 'form-control', 'reward',
            'Reward', array('col-md-4', 'pb-2', 'form-floating'), false);

        echo FieldSelect::GetField('promoted'.$nameA, strval($operative->promoted), 'form-control', 'promoted',
            'Promoted?', array('col-md-4','pb-2','form-floating'), true, Definitions::arrayYesNo());

        echo FieldSelect::GetField('opStatus'.$nameA, strval($operative->status), 'form-control', 'status',
            'Status', array('col-md-4','pb-2','form-floating'), false, Definitions::$operativeStatus);

        if($multi) {
            echo Field::repeatButton($lastElement);
        }
    }
}