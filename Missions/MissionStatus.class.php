<?php
declare(strict_types = 1);

namespace XCOMDatabank\Missions;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class MissionStatus
{
    public ?int $id;
    public string $name;
    public bool $enabled;
    public ?string $notes;

    function __construct() {
        $this->id = null;
        $this->name = "";
        $this->enabled = true;
        $this->notes = null;
    }

    public function newMissionStatus(array $missionStatus) : void {
        $this->validateMissionStatus($missionStatus);

        $query = "INSERT INTO xcom_mission_status VALUES (NULL, :name, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
    }

    public function getMissionStatus(int $id) : void {
        $query = "SELECT * FROM xcom_mission_status WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

        $this->id = $id;
        $this->name = $row['name'];
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
    }

    public function editMissionStatus(array $missionStatus) : void {
        $this->validateMissionStatus($missionStatus);

        $query = "UPDATE xcom_mission_status SET name = :name, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[3] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
    }

    private function validateMissionStatus(array $submit) : void {
        // If a Mission Status ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'mission_status', false, "Mission Status ID");
        }

        // Make sure name is a valid, non-null string
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Mission Status Description");

        // Make sure enabled is some form of boolean value
        $this->enabled = Validate::testTF($submit['enabled'], false, "Mission Status Enabled");

        // Make sure notes is a valid string or null
        $this->notes = Validate::testString($submit['notes'] ?? null, -1, -1, true, "Mission Status Description");
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editMissionStatus($submit);
            } else {
                return Error::returnError("Mission Status ID is set, but Mission Status ID is not numeric");
            }
        } else {
            $this->newMissionStatus($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getMissionStatusForm(MissionStatus $missionStatus) : void {
        $id = $missionStatus->id;
        if($id != "" and $id !== null and is_numeric($id)) {
            echo FieldHidden::getField('id',strval($id));
        }

        // Print Mission Status Name Form Field
        echo FieldText::getField('name', $missionStatus->name, 'form-control', 'name',
            'Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        // Print Mission Status Notes Field
        echo FieldText::getField('notes', $missionStatus->notes, 'form-control', 'notes',
            'Mission Status Notes', array('col-md-7','pb-2','form-floating'), false);

        echo FieldSelect::getField('enabled', strval($missionStatus->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-2','pb-2','form-floating'), true, Definitions::arrayYesNo());
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_mission_status ORDER BY enabled DESC, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table mission-status">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Mission Status</div>'."\n";
        $listString .= '<div class="col-2">Enabled</div>'."\n";
        $listString .= '<div class="col-6">Notes</div>'."\n";
        $listString .= '</div>'."\n";

        while ($row = $queryResult->fetch()) {

            if($row['enabled']) {
                $rowClass = "row-green";
            } else {
                $rowClass = "row-red";
            }

            $listString .= '<div class="row '.$rowClass.'">'."\n";

            $listString .= '<div class="col-4">';
            $listString .= '<a href="/mission/mission-status.php?id='.$row['id'].'">'.$row['name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';

            if($row['enabled']) {
                $listString .= "Enabled";
            } else {
                $listString .=  "Disabled";
            }

            $listString .= '</div>'."\n";
            $listString .= '<div class="col-6">';
            $listString .= $row['notes'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getMissionStatusArray(): array {
        $query = "SELECT * FROM xcom_mission_status WHERE enabled = true ORDER BY id";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $typeArray = array(
            array(
                'text' => "Select Mission Status",
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

    private function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[2] = array("param" => ":notes", "var" => $this->notes, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public static function getStatusByID(int $id): string {
        $query = "SELECT name FROM xcom_mission_status WHERE enabled = true and id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        $row = $queryResult->fetch();
        return $row['name'];
    }
}