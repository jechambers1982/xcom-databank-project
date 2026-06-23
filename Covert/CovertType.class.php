<?php
declare(strict_types = 1);

namespace XCOMDatabank\Covert;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextarea;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;
	
class CovertType
{
	public ?int $id;
	public string $name;
    public string $mission;
    public bool $enabled;
    public ?string $notes;
		
	function __construct() {
        $this->id = null;
		$this->name = "";
        $this->mission = "";
        $this->enabled = false;
        $this->notes = null;
	}
		
	public function newCovertType($covertType) {
        $this->validateCovertType($covertType);

        $query = "INSERT INTO xcom_covert_type VALUES (NULL, :name, :mission, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getCovertType(int $id) {
        $query = "SELECT * FROM xcom_covert_type WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->name = $row['name'];
        $this->mission = $row['mission'];
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
	}
		
	public function editCovertType($covertType) {
        $this->validateCovertType($covertType);

        $query = "UPDATE xcom_covert_type SET name = :name, mission = :mission, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[4] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateCovertType(array $submit): void {
        // If a Covert Type ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'covert_type', false, "Covert Type ID");
        }

        // Name
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Covert Type Name");

        // Mission
        $this->mission = Validate::testString($submit['mission'], -1, -1, false, "Covert Type Mission");

        // Enabled?
        $this->enabled = Validate::testTF($submit['enabled'], false, "Covert Type Enabled");

        // Notes
        $this->notes = Validate::testString($submit['notes'], -1, -1, true, "Covert Type Notes");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":mission", "var" => $this->mission, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[3] = array("param" => ":notes", "var" => $this->notes ?? null, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editCovertType($submit);
            } else {
                return Error::returnError("Covert Type ID is set, but Covert Type ID is not numeric");
            }
        } else {
            $this->newCovertType($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getCovertTypeForm(CovertType $covertType) {
        if(!empty($covertType->id) and is_numeric($covertType->id)) {
            echo FieldHidden::getField('id',strval($covertType->id));
        }

        echo FieldText::getField('name', $covertType->name, 'form-control', 'name',
            'Name', array('col-md-5', 'pb-2', 'form-floating'), true);

        echo FieldText::getField('mission', $covertType->mission, 'form-control', 'mission',
            'Mission', array('col-md-5','pb-2','form-floating'), true);

        echo FieldSelect::GetField('enabled', strval($covertType->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-2','pb-2','form-floating'), true, Definitions::arrayYesNo());

        echo FieldTextarea::getField('notes', $covertType->notes, 'form-control', 'notes',
            'Notes', array('col-md-6','pb-2','form-floating'), false, '3');
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_covert_type ORDER BY enabled DESC, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table info-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-5">Name</div>'."\n";
        $listString .= '<div class="col-5">Mission</div>'."\n";
        $listString .= '<div class="col-2">Enabled</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            if($row['enabled'] == 1) {
                $enabledClass = "row-green";
            } else {
                $enabledClass = "row-red";
            }

            $listString .= '<div class="row '.$enabledClass.'">'."\n";
            $listString .= '<div class="col-5">';
            $listString .= '<a href="/covert/covert-type.php?id='.$row['id'].'">'.$row['name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-5">';
            $listString .= $row['mission'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['enabled'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getCovertTypeArray(): array {
        $query = "SELECT * FROM xcom_covert_type WHERE enabled = true ORDER BY name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $typeArray = array();

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['name'],
                'value' => $item['id'],
            );
            array_push($typeArray, $newArray);
        }

        return $typeArray;
    }
}