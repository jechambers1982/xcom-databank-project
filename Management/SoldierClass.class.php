<?php
declare(strict_types = 1);

namespace XCOMDatabank\Management;

use PDO;
use XCOMDatabank\Forms\FieldFile;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextarea;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class SoldierClass
{
	public ?int $id;
	public string $name;
	public ?string $icon;
    public bool $enabled;
    public ?string $notes;
		
	function __construct() {
        $this->id = null;
		$this->name = "";
		$this->icon = null;
        $this->enabled = false;
        $this->notes = null;
	}
		
	public function newClass($class) {
        $this->validateClass($class);

        $query = "INSERT INTO xcom_class VALUES (NULL, :name, :icon, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getClass(int $id) {
        $query = "SELECT * FROM xcom_class WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
			
		$this->id = $id;
		$this->name = $row['name'];
		$this->icon = $row['icon'];
		$this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
	}
		
	public function editClass($class) {
        $this->validateClass($class);

        $query = "UPDATE xcom_class SET name = :name, icon = :icon, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[4] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateClass(array $submit): void {
        // If a Class ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'class', false, "Class ID");
        }

        // Make sure name is valid
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Class Name");

        // Make sure icon is valid or null
        $this->icon = Validate::testImage($submit['icon'], 'class', $this->name, true, "Class Image");

        // Make sure enabled is valid
        $this->enabled = Validate::testTF($submit['enabled'], false, "Class Enabled");

        // Make sure notes is a string or null
        $this->notes = Validate::testString($submit['notes'], -1, -1, true, "Class Notes");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":icon", "var" => $this->icon ?? null, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[3] = array("param" => ":notes", "var" => $this->notes ?? null, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editClass($submit);
            } else {
                return Error::returnError("Class ID is set, but Class ID is not numeric");
            }
        } else {
            $this->newClass($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getClassForm(SoldierClass $class) {
        if(!empty($class->id) and is_numeric($class->id)) {
            echo FieldHidden::getField('id',strval($class->id));
        }

        echo FieldText::getField('name', $class->name, 'form-control', 'name',
            'Class Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldSelect::getField('enabled', $class->enabled ? "1" : "0", 'form-control', 'enabled',
            'Enabled?', array('col-md-3', 'pb-2', 'form-floating'), false, Definitions::arrayYesNo());

        echo FieldTextarea::getField('notes', $class->notes, 'form-control', 'notes',
            'Notes', array('col-md-6','pb-2','form-floating'), false, '5');

        if(!empty($class->icon)) {
            echo FieldHidden::getField('icon_current', strval($class->icon));
        }

        echo FieldFile::getField('icon', 'https://xcom-databank.games'.$class->icon, 'icon', 'form-control', false, 'jpg, gif, jpeg, png',
            'Icon', array('col-md-6','pb-2','form-floating'));
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_class ORDER BY enabled DESC, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-2">Icon</div>'."\n";
        $listString .= '<div class="col-4">Name</div>'."\n";
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
            $listString .= '<div class="col-2">';
            $listString .= '<img src="https://xcom-databank.games/'.$row['icon'].'" alt="Rank Icon" width="64">';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= '<a href="/management/class.php?id='.$row['id'].'">'.$row['name'].'</a>';
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

    public static function getClassArray(): array {
        $query = "SELECT * FROM xcom_class WHERE enabled = true ORDER BY name";
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

    public static function getClassArrayAll(): array {
        $query = "SELECT * FROM xcom_class ORDER BY enabled DESC, name";
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

    public static function getClassNameByID(int $id): string {
        $query = "SELECT name FROM xcom_class WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);
        $queryResult = Database::runQuery('select', $query, $params);
        $result = $queryResult->fetch();

        return $result['name'];
    }

    public static function getClassIDByName(string $name): int {
        $query = "SELECT id FROM xcom_class WHERE name = :name";
        $params[0] = array("param" => ":name", "var" => $name, "type" => PDO::PARAM_STR,);
        $queryResult = Database::runQuery('select', $query, $params);
        $result = $queryResult->fetch();

        return intval($result['id']);
    }
}