<?php
declare(strict_types = 1);

namespace XCOMDatabank\Management;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextarea;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class Sitrep
{
	public ?int $id;
	public string $name;
	public string $description;
    public bool $enabled;
    public ?string $notes;
		
	function __construct() {
        $this->id = null;
		$this->name = "";
		$this->description = "";
        $this->enabled = true;
        $this->notes = null;
	}
		
	public function newSitrep($sitrep) {
        $this->validateSitrep($sitrep);

        $query = "INSERT INTO xcom_sitrep VALUES (NULL, :name, :description, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getSitrep(int $id) {
        $query = "SELECT * FROM xcom_sitrep WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->name = $row['name'];
		$this->description = $row['description'];
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
	}
		
	public function editSitrep($sitrep) {
        $this->validateSitrep($sitrep);

        $query = "UPDATE xcom_sitrep SET name = :name, description = :description, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[4] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateSitrep(array $submit) : void {
        // If a Sitrep ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'sitrep', false, "SITREP ID");
        }

        // Make sure name is a valid, non-null string
        $this->name = Validate::testString($submit['name'], -1, -1, false, "SITREP Name");

        // Make sure description is a valid, non-null string
        $this->description = Validate::testString($submit['description'], -1, -1, false, "SITREP Description");

        // Make sure enabled is some form of boolean value
        $this->enabled = Validate::testTF($submit['enabled'], false, "SITREP Enabled");

        // Make sure notes is a valid string or null
        $this->notes = Validate::testString($submit['notes'] ?? null, -1, -1, true, "SITREP Description");
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editSitrep($submit);
            } else {
                return Error::returnError("SITREP ID is set, but SITREP ID is not numeric");
            }
        } else {
            $this->newSitrep($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getSitrepForm(Sitrep $sitrep) : void {
        $id = $sitrep->id;
        if($id != "" and $id !== null and is_numeric($id)) {
            echo FieldHidden::getField('id',strval($id));
        }

        // Print Sitrep Name Form Field
        echo FieldText::getField('name', $sitrep->name, 'form-control', 'name',
            'Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        // Print SITREP Description Form Field
        echo FieldTextarea::getField('description', $sitrep->description, 'form-control', 'description',
            'Description', array('col-md-3', 'pb-2', 'form-floating'), true, '5');

        // Print SITREP Notes Field
        echo FieldText::getField('notes', $sitrep->notes, 'form-control', 'notes',
            'Notes', array('col-md-4','pb-2','form-floating'), false);

        echo FieldSelect::getField('enabled', strval($sitrep->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-2','pb-2','form-floating'), true, Definitions::arrayYesNo());
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_sitrep ORDER BY enabled DESC, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table sitrep">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-3">SITREP</div>'."\n";
        $listString .= '<div class="col-3">Description</div>'."\n";
        $listString .= '<div class="col-2">Enabled</div>'."\n";
        $listString .= '<div class="col-4">Notes</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {

            if($row['enabled']) {
                $rowClass = "row-green";
            } else {
                $rowClass = "row-red";
            }

            $listString .= '<div class="row '.$rowClass.'">'."\n";
            $listString .= '<div class="col-3">';
            $listString .= '<a href="/management/sitrep.php?id='.$row['id'].'">'.$row['name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $row['description'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';

            if($row['enabled']) {
                $listString .= "Enabled";
            } else {
                $listString .=  "Disabled";
            }

            $listString .= '</div>'."\n";
            $listString .= '<div class="col-4">';
            $listString .= $row['notes'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getSitrepArray(): array {
        $query = "SELECT * FROM xcom_sitrep WHERE enabled = true ORDER BY name";
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

    public static function getSitrepArrayByMission($missionID): array {
        $query = "SELECT sitrep_id FROM xcom_mission_sitrep WHERE mission_id = :mission";
        $params[0] = array("param" => ":mission", "var" => $missionID, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        $sitrepArray = array();
        while ($row = $queryResult->fetch())
        {
            array_push($sitrepArray, $row['sitrep_id']);
        }
        return $sitrepArray;
    }

    public static function getSitrepName($id): string {
        $query = "SELECT name FROM xcom_sitrep WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        $row = $queryResult->fetch();

        return $row['name'];
    }

    private function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":description", "var" => $this->description, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[3] = array("param" => ":notes", "var" => $this->notes, "type" => PDO::PARAM_STR,);
        return $params;
    }
}