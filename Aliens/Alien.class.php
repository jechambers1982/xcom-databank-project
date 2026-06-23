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

class Alien {

	public ?int $id;
	public int $type;
	public string $name;
    public int $minForce;
    public int $maxForce;
    public bool $enabled;
    public ?string $notes;
	
	function __construct() {
        $this->id = null;
		$this->name = "";
		$this->type = -1;
		$this->minForce = 0;
        $this->maxForce = 0;
        $this->enabled = false;
        $this->notes = null;
	}
	
	public function newAlien($alien) {
        $this->validateAlien($alien);

        $query = "INSERT INTO xcom_aliens VALUES (NULL, :type, :name, :minForce, :maxForce, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
	
	public function getAlien(int $id) {
        $query = "SELECT * FROM xcom_aliens WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->type = intval($row['type_id']);
		$this->name = $row['name'];
		$this->minForce = intval($row['min_force']);
        $this->maxForce = intval($row['max_force']);
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
	}
	
	public function editAlien($alien) {
        $this->validateAlien($alien);

        $query = "UPDATE xcom_aliens SET name = :name, type_id = :type, min_force = :minForce, max_force = :maxForce, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[6] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateAlien(array $submit): void {
        // If an Alien ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'aliens', false, "Alien ID");
        }

        // Make sure Alien name is valid
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Alien Name");

        // Make sure Alien Type index is valid
        $this->type = Validate::testIndex($submit['type'], 'alien_type', false, "Alien Type");

        // Make sure minimum force level is valid
        $this->minForce = Validate::testInteger(intval($submit['min_force']), 1, 20, false, "Alien Minimum Force Level");

        // Make sure maximum force level is valid
        $this->maxForce = Validate::testInteger(intval($submit['max_force']), 1, 20, false, "Alien Maximum Force Level");

        // Make sure enabled value is valid
        $this->enabled = Validate::testTF($submit['enabled'], false, "Alien Enabled");

        // Make sure notes is string or null
        $this->notes = Validate::testString($submit['notes'], -1, -1, true, "Alien Notes");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":type", "var" => $this->type, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":minForce", "var" => $this->minForce, "type" => PDO::PARAM_INT,);
        $params[3] = array("param" => ":maxForce", "var" => $this->maxForce, "type" => PDO::PARAM_INT,);
        $params[4] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[5] = array("param" => ":notes", "var" => $this->notes, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editAlien($submit);
            } else {
                return Error::returnError("Alien ID is set, but Alien ID is not numeric");
            }
        } else {
            $this->newAlien($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getAlienForm(Alien $alien)
    {
        if (!empty($alien->id) and is_numeric($alien->id)) {
            echo FieldHidden::getField('id', strval($alien->id));
        }

        echo FieldSelect::getField('type', strval($alien->type), 'form-control', 'type',
            'Alien Type', array('col-md-3', 'pb-2', 'form-floating'), true, AlienType::getAlienTypeArrayAll());

        echo FieldText::getField('name', $alien->name, 'form-control', 'name',
            'Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldTextNum::getField('min_force', strval($alien->minForce), 'form-control', 'min-force',
            'Min. FL', array('col-md-2', 'pb-2', 'form-floating'), true, false, "1", "20");

        echo FieldTextNum::getField('max_force', strval($alien->maxForce), 'form-control', 'max-force',
            'Max. FL', array('col-md-2', 'pb-2', 'form-floating'), true, false, "1", "20");

        echo FieldSelect::getField('enabled', $alien->enabled ? "1" : "0", 'form-control', 'enabled',
            'Enabled?', array('col-md-2', 'pb-2', 'form-floating'), true, Definitions::arrayYesNo());

        echo FieldTextarea::getField('notes', strval($alien->notes), 'form-control', 'enabled',
            'Notes', array('col-md-6', 'pb-2', 'form-floating'), true, "3");
    }

    public static function getListPage(): void {
        $query = "SELECT alien.name as name, type.name as type, alien.id as id, alien.enabled as enabled, alien.min_force as min, alien.max_force as max, alien.notes as notes
			FROM xcom_aliens as alien
				INNER JOIN xcom_alien_type as type ON alien.type_id = type.id
			ORDER BY alien.enabled DESC, alien.min_force, type.name, alien.name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-3">Name</div>'."\n";
        $listString .= '<div class="col-2">Type</div>'."\n";
        $listString .= '<div class="col-1">FLs</div>'."\n";
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
            $listString .= '<a href="/aliens/aliens.php?id='.$row['id'].'">'.$row['name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['type'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-1">';
            $listString .= $row['min'].' - '.$row['max'];
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

    public static function getAliensArrayByType(int $type): array {
        $forceLevel = Info::getForceLevel();
        $query = "SELECT * FROM xcom_aliens WHERE enabled = 1 and type_id = :type and min_force <= :FL and max_force >= :FL ORDER BY name";
        $params[0] = array("param" => ":type", "var" => $type, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":FL", "var" => $forceLevel, "type" => PDO::PARAM_INT,);

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