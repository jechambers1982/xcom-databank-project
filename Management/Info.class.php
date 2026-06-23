<?php
declare(strict_types = 1);

namespace XCOMDatabank\Management;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Forms\FieldTextNumDate;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class Info
{
	public ?int $id;
	public string $field;
	public string $value;
	public string $type;
		
	function __construct() {
        $this->id = null;
		$this->field = "";
		$this->value = "";
		$this->type = "";
	}
		
	public function newInfo($info) {
        $this->validateInfo($info);

        $query = "INSERT INTO xcom_info VALUES (NULL, :field, :value, :type)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getInfo(int $id) {
        $query = "SELECT * FROM xcom_info WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->field = $row['field'];
		$this->value = $row['value'];
		$this->type = $row['type'];
	}
		
	public function editInfo($info) {
        $this->validateInfo($info);

        $query = "UPDATE xcom_info SET field = :field, value = :value, type = :type WHERE id = :id";
        $params = $this->getParams();
        $params[3] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateInfo(array $submit): void {
        // If an Info ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'info', false, "Info ID");
        }

        // Make sure field is a valid, non-null string
        $this->field = Validate::testString($submit['field'], -1, -1, false, "Info Field");

        // Make sure value is a valid string or null
        $this->value = Validate::testString($submit['value'], -1, -1, false, "Info Value");

        // Make sure type is a valid string or null
        $this->type = Validate::testString($submit['type'], -1, -1, false, "Info Type");
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editInfo($submit);
            } else {
                return Error::returnError("Info ID is set, but Info ID is not numeric");
            }
        } else {
            $this->newInfo($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getInfoForm(Info $info) {
        if(!empty($info->id) and is_numeric($info->id)) {
            echo FieldHidden::getField('id',strval($info->id));
        }

        echo FieldText::getField('field', $info->field, 'form-control', 'field',
            'Field', array('col-md-4', 'pb-2', 'form-floating'), true);

        if($info->type == "string") {
            echo FieldText::getField('value', $info->value, 'form-control', 'value',
                'Value', array('col-md-4','pb-2','form-floating'), true);
        }
        elseif($info->type == "number") {
            echo FieldTextNum::getField('value', $info->value, 'form-control', 'value',
                'Value', array('col-md-4','pb-2','form-floating'), true, false, '0', '100');
        }
        elseif($info->type == "date") {
            echo FieldTextNumDate::getField('value', $info->value, 'form-control', 'value',
                'Value', array('col-md-4','pb-2','form-floating'), true, false, '2035-02-28', '2037-12-31');
        }


        echo FieldText::getField('type', $info->type, 'form-control', 'type',
            'Datatype', array('col-md-4','pb-2','form-floating'), true);
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_info ORDER BY field";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table info-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Info Field</div>'."\n";
        $listString .= '<div class="col-4">Info Value</div>'."\n";
        $listString .= '<div class="col-4">Info Type</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            $listString .= '<div class="row">'."\n";
            $listString .= '<div class="col-4">';
            $listString .= '<a href="/management/info.php?id='.$row['id'].'">'.$row['field'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['value'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['type'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public function getParams(): array {
        $params[0] = array("param" => ":field", "var" => $this->field, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":value", "var" => $this->value, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":type", "var" => $this->type, "type" => PDO::PARAM_STR,);
        return $params;
    }
	
	public static function getCurrentDate() : string {
        $query = "SELECT value FROM xcom_info WHERE field = 'Current Date'";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);
		
		$row =  $queryResult->fetch();

		return date('F d, Y', strtotime($row['value']));
	}
	
	public static function getCurrentDateShort(): string {
        $query = "SELECT value FROM xcom_info WHERE field = 'Current Date'";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);
		
		$row = $queryResult->fetch();
		
		return $row['value'];
	}
	
	public static function getCrew(): string {
        $query = "SELECT * FROM xcom_info WHERE field LIKE CONCAT('%', :field, '%')";
        $params[0] = array("param" => ":field", "var" => "crew", "type" => PDO::PARAM_STR,);

        $queryResult = Database::runQuery('select', $query, $params);

		$crewArray = [];
		while ($row = $queryResult->fetch()) {
			$crewArray[$row['field']] = $row['value'];
		}

		return $crewArray['Crew']." / ".$crewArray['Crew Available'];
	}
	
	public static function getAvatarPips(): string {
        $query = "SELECT * FROM xcom_info WHERE field = 'Avatar Pips'";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);
		
		$row = $queryResult->fetch();
		
		$totalPips = 12;
		$activePips = $row['value'];
		$remainingPips = $totalPips - $activePips;
		$pipString = "";
		
		for($i = $activePips; $i > 0; $i--)
		{
		  $pipString .= '<i class="fas fa-square"></i>';
		}
		
		for($i = $remainingPips; $i > 0; $i--)
		{
		  $pipString .= '<i class="far fa-square"></i>';
		}

		return $pipString;
	}

    public static function getTitle(): string {
        $query = "SELECT value FROM xcom_info WHERE field = 'Title'";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $row = $queryResult->fetch();

        return $row['value'];
    }

    public static function getForceLevel(): int {
        $query = "SELECT value FROM xcom_info WHERE field = 'Force Level'";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $row = $queryResult->fetch();

        return intval($row['value']);
    }
}