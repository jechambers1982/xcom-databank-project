<?php
declare(strict_types = 1);

namespace XCOMDatabank\Aliens;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextarea;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Management\Info;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class AlienType {

	public ?int $id;
	public string $name;
	public string $faction;
    public int $minForce;
    public bool $enabled;
    public ?string $notes;
	
	function __construct() {
        $this->id = null;
		$this->name = "";
		$this->faction = "";
        $this->minForce = 0;
        $this->enabled = false;
        $this->notes = null;
	}
	
	public function newAlienType($alienType) {
        $this->validateAlienType($alienType);

        $query = "INSERT INTO xcom_alien_type VALUES (NULL, :name, :faction, :minForce, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
	
	public function getAlienType(int $id) {
        $query = "SELECT * FROM xcom_alien_type WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

		$this->id = $id;
		$this->name = $row['name'];
		$this->faction = $row['faction'];
		$this->minForce = intval($row['min_force']);
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
	}
	
	public function editAlienType($alienType) {
        $this->validateAlienType($alienType);

        $query = "UPDATE xcom_alien_type SET name = :name, faction = :faction, min_force = :minForce, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[5] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateAlienType(array $submit): void {
        // If a AlienType ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'alien_type', false, "Alien Type ID");
        }

        // Make Alien Type name is valid
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Alien Type Name");

        // Make sure soldier2 ID is valid
        $this->faction = Validate::testArray($submit['faction'], Definitions::getAlienFaction(), false, "Alien Type Faction");

        // Make sure minimum force level is valid
        $this->minForce = Validate::testInteger(intval($submit['min_force']), 1, 20, false, "Alien Type Minimum Force Level");

        // Make sure enabled value is valid
        $this->enabled = Validate::testTF($submit['enabled'], false, "Alien Type Enabled");

        // Make sure notes is string or null
        $this->notes = Validate::testString($submit['notes'], -1, -1, true, "Alien Type Notes");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":faction", "var" => $this->faction, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":minForce", "var" => $this->minForce, "type" => PDO::PARAM_INT,);
        $params[3] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[4] = array("param" => ":notes", "var" => $this->notes, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editAlienType($submit);
            } else {
                return Error::returnError("AlienType ID is set, but AlienType ID is not numeric");
            }
        } else {
            $this->newAlienType($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getAlienTypeForm(AlienType $alienType)
    {
        if (!empty($alienType->id) and is_numeric($alienType->id)) {
            echo FieldHidden::getField('id', strval($alienType->id));
        }

        echo FieldText::getField('name', $alienType->name, 'form-control', 'name',
            'Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldSelect::getField('faction', $alienType->faction, 'form-control', 'faction',
            'Faction', array('col-md-3', 'pb-2', 'form-floating'), true, Definitions::getAlienFaction());

        echo FieldTextNum::getField('min_force', strval($alienType->minForce), 'form-control', 'min-force',
            'Min. FL', array('col-md-3', 'pb-2', 'form-floating'), true, false, "1", "20");

        echo FieldSelect::getField('enabled', $alienType->enabled ? "1" : "0", 'form-control', 'enabled',
            'Enabled?', array('col-md-3', 'pb-2', 'form-floating'), true, Definitions::arrayYesNo());

        echo FieldTextarea::getField('notes', strval($alienType->notes), 'form-control', 'enabled',
            'Notes', array('col-md-6', 'pb-2', 'form-floating'), true, "3");
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_alien_type ORDER BY enabled DESC, min_force, faction, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-3">Name</div>'."\n";
        $listString .= '<div class="col-2">Faction</div>'."\n";
        $listString .= '<div class="col-1">Min FL</div>'."\n";
        $listString .= '<div class="col-2">Enabled</div>'."\n";
        $listString .= '<div class="col-4">Notes</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            if($row['enabled'] == 1) {
                $enabledClass = "row-green";
            } else {
                $enabledClass = "row-red";
            }

            $listString .= '<div class="row '.$enabledClass.'">'."\n";
            $listString .= '<div class="col-3">';
            $listString .= '<a href="/aliens/alien-types.php?id='.$row['id'].'">'.$row['name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['faction'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-1">';
            $listString .= $row['min_force'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['enabled'] ? "Yes" : "No";
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['notes'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getAlienTypeByAlien(int $alienID): int {
        $query = "SELECT type_id FROM xcom_aliens WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $alienID, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

        return intval($row['type_id']);
    }

    public static function getAlienTypeArray(): array {
        $forceLevel = Info::getForceLevel();
        $query = "SELECT * FROM xcom_alien_type WHERE enabled = 1 and min_force <= :forceLevel ORDER BY name";
        $params[0] = array("param" => ":forceLevel", "var" => $forceLevel, "type" => PDO::PARAM_INT,);

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

    public static function getAlienTypeArrayAll(): array {
        $forceLevel = Info::getForceLevel();
        $query = "SELECT * FROM xcom_alien_type ORDER BY enabled DESC, min_force, name";
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