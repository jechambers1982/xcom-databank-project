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

class DarkEvent
{
	public ?int $id;
		
	public string $name;
	public ?string $description;
    public bool $enabled;
    public ?string $notes;
    public bool $active;
		
	function __construct() {
        $this->id = null;
		$this->name = "";
		$this->description = null;
        $this->enabled = true;
        $this->notes = null;
        $this->active = false;
	}
		
	public function newDarkEvent($darkEvent) {
        $this->validateDarkEvent($darkEvent);

        $query = "INSERT INTO xcom_dark_event VALUES (NULL, :name, :description, :enabled, :notes, :active)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getDarkEvent(int $id) {
        $query = "SELECT * FROM xcom_dark_event WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

        $this->id = $id;
        $this->name = $row['name'];
        $this->description = $row['description'];
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
        $this->active = boolval($row['active']);
	}
		
	public function editDarkEvent($darkEvent) {
        $this->validateDarkEvent($darkEvent);

        $query = "UPDATE xcom_dark_event SET name = :name, description = :description, enabled = :enabled, notes = :notes, active = :active WHERE id = :id";
        $params = $this->getParams();
        $params[5] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateDarkEvent(array $submit) : void {
        // If a Dark Event ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'dark_event', false, "Dark Event ID");
        }

        // Make sure name is a valid, non-null string
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Dark Event Name");

        // Make sure description is a string or null
        $this->description = Validate::testString($submit['description'] ?? null, -1, -1, true, "Dark Event Description");

        // Make sure enabled is some form of boolean value
        $this->enabled = Validate::testTF($submit['enabled'], false, "Dark Event Enabled");

        // Make sure notes is a valid string or null
        $this->notes = Validate::testString($submit['notes'] ?? null, -1, -1, true, "Dark Event Description");

        // Make sure active is some form of boolean value
        $this->active = Validate::testTF($submit['active'], false, "Dark Event Active");
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editDarkEvent($submit);
            } else {
                return Error::returnError("Dark Event ID is set, but Dark Event ID is not numeric");
            }
        } else {
            $this->newDarkEvent($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getDarkEventForm(DarkEvent $darkEvent) : void {
        $id = $darkEvent->id;
        if($id != "" and $id !== null and is_numeric($id)) {
            echo FieldHidden::getField('id',strval($id));
        }

        // Print Dark Event Name Form Field
        echo FieldText::getField('name', $darkEvent->name, 'form-control', 'name',
            'Name', array('col-md-4', 'pb-2', 'form-floating'), true);

        // Print Dark Event Description Form Field
        echo FieldTextarea::getField('description', $darkEvent->description, 'form-control', 'description',
            'Description', array('col-md-4', 'pb-2', 'form-floating'), true, '5');

        // Print Dark Event Notes Field
        echo FieldText::getField('notes', $darkEvent->notes, 'form-control', 'notes',
            'Dark Event Notes', array('col-md-4','pb-2','form-floating'), false);

        echo FieldSelect::getField('enabled', strval($darkEvent->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-2','pb-1','form-floating'), true, Definitions::arrayYesNo());

        echo FieldSelect::getField('active', strval($darkEvent->active), 'form-control', 'active',
            'Active?', array('col-md-2','pb-1','form-floating'), true, Definitions::arrayYesNo());
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_dark_event ORDER BY active DESC, enabled DESC, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table dark-event">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-3">Dark Event</div>'."\n";
        $listString .= '<div class="col-4">Description</div>'."\n";
        $listString .= '<div class="col-1">Enabled</div>'."\n";
        $listString .= '<div class="col-2">Notes</div>'."\n";
        $listString .= '<div class="col-2">Active</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {

            if($row['enabled']) {
                $rowClass = "row-green";
            } else {
                $rowClass = "row-red";
            }

            $listString .= '<div class="row '.$rowClass.'">'."\n";
            $listString .= '<div class="col-3">';
            $listString .= '<a href="/management/dark-event.php?id='.$row['id'].'">'.$row['name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['description'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-1">';

            if($row['enabled']) {
                $listString .= "Enabled";
            } else {
                $listString .=  "Disabled";
            }

            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['notes'];
            $listString .= '</div>'."\n";

            if($row['active']) {
                $listString .= '<div class="col-2">';
                $listString .= "Active";
                $listString .= '</div>'."\n";
            } else {
                $listString .= '<div class="col-2">';
                $listString .=  "Not Active";
                $listString .= '</div>'."\n";
            }

            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getDarkEventArray(): array {
        $query = "SELECT * FROM xcom_dark_event WHERE enabled = true ORDER BY name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $typeArray = array(
            array(
                'text' => "Select Dark Event",
                'value' => "",
            ),
        );

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['name'],
                'value' => $item['id'],
            );
            array_push($typeArray, $newArray);
        }

        return $typeArray;
    }

    private function getParams(): array
    {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":description", "var" => $this->description, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[3] = array("param" => ":notes", "var" => $this->notes, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":active", "var" => $this->active, "type" => PDO::PARAM_BOOL,);
        return $params;
    }

    public static function getDarkEventName(int $id): string {
        $darkEvent = new DarkEvent;
        $darkEvent->getDarkEvent($id);

        return $darkEvent->name;
    }
}