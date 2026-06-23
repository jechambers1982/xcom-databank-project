<?php
declare(strict_types = 1);

namespace XCOMDatabank\Covert;

use DateTime;
use Exception;
use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextNumDate;
use XCOMDatabank\Management\Info;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class CovertAction
{
	public ?int $id;
	public string $faction;				// Skirmishers, Reapers, or Templars
	public int $type;					// This will be the label of text in the list on the left
	public string $reward;				// Covert Op reward, e.g.: Faction Soldier
	public string $location;			// Sector action is taking place
	public string $startDate;
	public string $endDate;
	public string $status;				// None, Ambush, Wounded, Captured, Killed
	public bool $is_chain;				// Is Action part of an Activity Chain?
		
	function __construct() {
        $this->id = null;
		$this->faction = "";
		$this->type = -1;
		$this->reward = "";
		$this->location = "";
        $this->startDate = Info::getCurrentDateShort();
        $this->endDate = Info::getCurrentDateShort();
		$this->status = "";
		$this->is_chain = false;
	}

    public function newCovertAction($covertAction) {
        $this->validateCovertAction($covertAction);

        $query = "INSERT INTO xcom_covert_action VALUES (NULL, :typeID, :faction, :reward, :location, :startDate, :endDate, :status, :isChain)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
			
	public function getCovertAction(int $id) {
        $query = "SELECT * FROM xcom_covert_action WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->type = intval($row['type_id']);
		$this->faction = $row['faction'];
		$this->reward = $row['reward'];
		$this->location = $row['location'];
		$this->startDate = $row['start_date'];
		$this->endDate = $row['end_date'];
		$this->status = $row['status'];
		$this->is_chain = boolval($row['is_chain']);
	}
		
	public function editCovertAction($covertAction) {
        $this->validateCovertAction($covertAction);

        $query = "UPDATE xcom_covert_action SET type_id = :typeID, faction = :faction, reward = :reward, location = :location, 
                        start_date = :startDate, end_date = :endDate, status = :status, is_chain = :isChain WHERE id = :id";
        $params = $this->getParams();
        $params[8] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateCovertAction(array $submit): void {
        // If a Covert Action ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'covert_action', false, "Covert Action ID");
        }

        // Covert Action Type ID
        $this->type = Validate::testIndex($submit['type_id'], 'covert_type', false, "Covert Action Type");

        // Faction
        $this->faction = Validate::testArray($submit['faction'], Definitions::getFactions(), false, "Covert Action Faction");

        // Reward
        $this->reward = Validate::testString($submit['reward'], -1, -1, false, "Covert Action Reward");

        // Faction
        $this->location = Validate::testArray($submit['sector'], Definitions::getSectors(), false, "Covert Action Sector");

        // Start Date
        $this->startDate = Validate::testDate($submit['start_date'], false, "Covert Action Start Date");

        // End Date
        $this->endDate = Validate::testDate($submit['end_date'], false, "Covert Action End Date");

        // Status
        $this->status = Validate::testArray($submit['status'], Definitions::getCovertStatus(), false, "Covert Action Status");

        // Is Chain
        $this->is_chain = Validate::testTF($submit['is_chain'], false, "Covert Action is Chain");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":typeID", "var" => $this->type, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":faction", "var" => $this->faction, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":reward", "var" => $this->reward, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":location", "var" => $this->location, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":startDate", "var" => $this->startDate, "type" => PDO::PARAM_STR,);
        $params[5] = array("param" => ":endDate", "var" => $this->endDate, "type" => PDO::PARAM_STR,);
        $params[6] = array("param" => ":status", "var" => $this->status, "type" => PDO::PARAM_STR,);
        $params[7] = array("param" => ":isChain", "var" => $this->is_chain, "type" => PDO::PARAM_BOOL,);
        return $params;
    }

    public function processForm(array $submit, string $redirect = ''): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editCovertAction($submit);
            } else {
                return Error::returnError("Covert Action ID is set, but Covert Action ID is not numeric");
            }
        } else {
            $this->newCovertAction($submit);
        }
        //header('Location: '.$redirect);
        return "";
    }

    public static function getCovertActionForm(CovertAction $covertAction) {
        if(!empty($covertAction->id) and is_numeric($covertAction->id)) {
            echo FieldHidden::getField('id',strval($covertAction->id));
        }

        echo FieldSelect::GetField('type_id', strval($covertAction->type), 'form-control', 'type-id',
            'Covert Action Type', array('col-md-3','pb-2','form-floating'), true, CovertType::getCovertTypeArray());

        echo FieldSelect::GetField('faction', $covertAction->faction, 'form-control', 'faction',
            'Faction', array('col-md-3','pb-2','form-floating'), true, Definitions::getFactions());

        echo FieldText::getField('reward', $covertAction->reward, 'form-control', 'reward',
            'Reward', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldSelect::GetField('sector', $covertAction->location, 'form-control', 'sector',
            'Sector', array('col-md-3','pb-2','form-floating'), true, Definitions::getSectors());


        echo FieldTextNumDate::getField('start_date', $covertAction->startDate, 'form-control', 'start-date',
            'Start Date', array('col-md-3','pb-2','form-floating'), true, false,'2035-02-28', '2037-12-31');

        echo FieldTextNumDate::getField('end_date', $covertAction->endDate, 'form-control', 'end-date',
            'End Date', array('col-md-3','pb-2','form-floating'), true, false,'2035-02-28', '2037-12-31');

        echo FieldSelect::GetField('status', $covertAction->status, 'form-control', 'status',
            'Status', array('col-md-3','pb-2','form-floating'), true, Definitions::getCovertStatus());

        echo FieldSelect::GetField('is_chain', strval($covertAction->is_chain), 'form-control', 'is-chain',
            'Is Chain?', array('col-md-3','pb-2','form-floating'), true, Definitions::arrayYesNo());
    }

    public static function getOperativesForm(CovertAction $covertAction): void {
        $query = "SELECT operative.id 
                FROM xcom_covert_operative as operative
                    INNER JOIN xcom_covert_action as action ON action.id = operative.action_id
            WHERE action.id = :actionID";
        $params[0] = array("param" => ":actionID", "var" => $covertAction->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        echo '<div class="repeat-parent col-12">'."\n";

        if($queryResult->rowCount() == 0) {
            $operativeRows[0]['id'] = null;
        } else {
            $operativeRows = $queryResult->fetchAll();
        }

        foreach($operativeRows as $operative) {
            echo '<div class="field-repeat mission-info row gx-3 gy-0">'."\n";

            $newOperative = new CovertOperative;
            if($operative['id'] > 0) {
                $newOperative->getCovertOperative(intval($operative['id']));
            }

            CovertOperative::getCovertOperativeForm($newOperative);

            echo '</div>'."\n";
        }

        echo '</div>';
    }

    public static function getListPage(): void {
        $query = "SELECT action.*, type.mission as mission
            FROM xcom_covert_action as action
            LEFT JOIN xcom_covert_type as type ON type.id = action.type_id
            ORDER BY FIELD(status, 'In Progress') desc, end_date desc";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table info-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Dates</div>'."\n";
        $listString .= '<div class="col-4">Covert Action</div>'."\n";
        $listString .= '<div class="col-4">Info</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            if($row['status'] == "Complete") {
                $rowClass = "row-green";
            } elseif($row['status'] == "Ambushed") {
                $rowClass = "row-red";
            } else {
                $rowClass = "row-yellow";
            }

            // Convert Date to better format
            try {
                $startDateTime = new DateTime($row['start_date']);
                $endDateTime = new DateTime($row['end_date']);
            } catch (Exception $e) {
                print "Error!: " . $e->getMessage() . "<br/>";
                die();
            }

            $newStartDate = $startDateTime->format('F j, Y');
            $newEndDate = $endDateTime->format('F j, Y');

            $listString .= '<div class="row '.$rowClass.'">'."\n";
            $listString .= '<div class="col-4">';
            $listString .= $newStartDate.'<br />'.$newEndDate;
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= '<a href="/covert/covert.php?id='.$row['id'].'">'.$row['mission'].'</a><br />'.$row['reward'].'<br />'.$row['faction'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= self::getOperativesList($row['id']);
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getOperativesList($actionID): string {
        $query = "SELECT operative.resource, operative.requirement, soldier.first_name, soldier.nickname, soldier.last_name
            FROM xcom_covert_operative as operative
                LEFT JOIN xcom_soldier as soldier ON operative.soldier_id = soldier.id
            WHERE operative.action_id = :actionID";
        $params[0] = array("param" => ":actionID", "var" => $actionID, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        $operativeString = "";
        while($operative = $queryResult->fetch()) {
            if($operativeString != "") {
                $operativeString .= '<br />';
            }

            if(empty($operative['first_name'])) {
                if($operative['requirement'] == "Scientist" or $operative['requirement'] == "Engineer") {
                    $operativeString .= $operative['resource'];
                } else {
                    $operativeString .= $operative['resource'].' '.$operative['requirement'];
                }

            } else {
                $operativeString .= $operative['first_name'].' ';
                if(!empty($operative['nickname'])) {
                    $operativeString .= '"'.$operative['nickname'].'" ';
                }
                $operativeString .= $operative['last_name'];
            }
        }

        return $operativeString;
    }
	
	public function getCovertType(): string {
        $query = "SELECT type.name
			FROM xcom_covert_type as type 
				INNER JOIN xcom_covert_action as action ON type.id = action.type_id
			WHERE action.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
		$row = $queryResult->fetch();
		
		return $row['name'];
	}

    public function getActivityChainBox(string $status): void {

    }
}