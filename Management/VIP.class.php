<?php
declare(strict_types = 1);

namespace XCOMDatabank\Management;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextNumDate;
use XCOMDatabank\Missions\Mission;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class VIP
{
	public ?int $id;
		
	public string $firstName;
	public string $lastName;
	public string $position;
	public string $joined;
	public string $recruited;
	public ?int $mission;
	public ?string $rumor;
		
	function __construct() {
        $this->id = null;
		$this->firstName = "";
		$this->lastName = "";
		$this->position = "";
		$this->joined = Info::getCurrentDateShort();
		$this->recruited = "";
		$this->mission = null;
		$this->rumor = null;
	}
		
	public function newVIP($vip) {
        $this->validateVIP($vip);

        $query = "INSERT INTO xcom_vip VALUES (NULL, :firstName, :lastName, :position, :joined, :recruited, :mission, :rumor)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getVIP(int $id) {
        $query = "SELECT * FROM xcom_vip WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->firstName = $row['first_name'];
		$this->lastName = $row['last_name'];
		$this->position = $row['position'];
		$this->joined = $row['joined'];
		$this->recruited = $row['recruited'];
		$this->mission = intval($row['mission']) ?: null;
		$this->rumor = $row['rumor'];
	}
		
	public function editVIP($vip) {
        $this->validateVIP($vip);

        $query = "UPDATE xcom_info SET field = :field, value = :value, type = :type WHERE id = :id";
        $params = $this->getParams();
        $params[7] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateVIP(array $submit): void {
        // If an Info ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'vip', false, "VIP ID");
        }

        // Make sure First Name is a valid, non-null string
        $this->firstName = Validate::testString($submit['first_name'], -1, -1, false, "VIP First Name");

        // Make sure Last Name is a valid, non-null string
        $this->lastName = Validate::testString($submit['last_name'], -1, -1, false, "VIP Last Name");

        // Make sure VIP position is valid
        $this->position = Validate::testArray($submit['type'], Definitions::getVipType(), false, "VIP Position");

        // Make sure Joined Date is valid
        $this->joined = Validate::testDate($submit['vip_date'], false, "VIP Join Date");

        // Make sure Recruitment Method is valid
        $this->recruited = Validate::testArray($submit['recruitment'], Definitions::getVipStatus(), false, "VIP Recruitment Method");

        // Make sure Mission ID is valid or null
        $this->mission = Validate::testIndex($submit['mission_id'], "mission", true, "VIP Mission ID");

        // Make sure Rumor Source is a String or null
        $this->rumor = Validate::testString($submit['rumor'], -1, -1, true, "VIP Rumor Source");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":firstName", "var" => $this->firstName, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":lastName", "var" => $this->lastName, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":position", "var" => $this->position, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":joined", "var" => $this->joined, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":recruited", "var" => $this->recruited, "type" => PDO::PARAM_STR,);
        $params[5] = array("param" => ":mission", "var" => $this->mission, "type" => PDO::PARAM_STR,);
        $params[6] = array("param" => ":rumor", "var" => $this->rumor, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editVIP($submit);
            } else {
                return Error::returnError("VIP ID is set, but Info ID is not numeric");
            }
        } else {
            $this->newVIP($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getVIPForm(VIP $vip) {
        if(!empty($vip->id) and is_numeric($vip->id)) {
            echo FieldHidden::getField('id',strval($vip->id));
        }

        echo FieldText::getField('first_name', $vip->firstName, 'form-control', 'first-name',
            'First Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldText::getField('last_name', $vip->lastName, 'form-control', 'last-name',
            'Last Name', array('col-md-3','pb-2','form-floating'), true);

        echo FieldSelect::GetField('type', $vip->position, 'form-control', 'position',
            'VIP Type', array('col-md-3','pb-2','form-floating'), true, Definitions::getVipType());

        echo FieldTextNumDate::getField('vip_date', $vip->joined, 'form-control', 'date',
            'Recruitment Date', array('col-md-3','pb-2','form-floating'), true, false,'2035-02-01', '2037-12-31');

        echo FieldSelect::GetField('recruitment', $vip->recruited, 'form-control', 'recruitment',
            'Recruitment Method', array('col-md-3','pb-2','form-floating'), true, Definitions::getVipStatus());

        echo FieldSelect::GetField('mission_id', strval($vip->mission), 'form-control', 'mission',
            'Mission (If Applicable)', array('col-md-3','pb-2','form-floating'), false, Mission::getMissionArray('true'));

        echo FieldText::getField('rumor', $vip->rumor, 'form-control', 'rumor',
            'Scan Rumor (If Applicable)', array('col-md-3','pb-2','form-floating'), false);
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_vip ORDER BY joined DESC, last_name, position";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table info-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Name</div>'."\n";
        $listString .= '<div class="col-4">Position</div>'."\n";
        $listString .= '<div class="col-4">Join Date</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            $listString .= '<div class="row">'."\n";
            $listString .= '<div class="col-4">';
            $listString .= '<a href="/management/vip.php?id='.$row['id'].'">'.$row['first_name'].' '.$row['last_name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['position'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['joined'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }
}