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

class ActivityChainType
{
    public ?int $id;
    public string $name;
    public bool $enabled;
    public ?string $notes;

    function __construct() {
        // Set Default Values
        $this->id = null;
        $this->name = "";
        $this->enabled = false;
        $this->notes = null;
    }

    public function newActivityChainType($activityChainType) {
        $this->validateActivityChainType($activityChainType);

        $query = "INSERT INTO xcom_activity_chain_type VALUES (NULL, :name, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
    }

    public function getActivityChainType(int $id) {
        $query = "SELECT * FROM xcom_activity_chain_type WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

        $this->id = $id;
        $this->name = $row['name'];
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
    }

    public function editActivityChainType($activityChainType) {
        $this->validateActivityChainType($activityChainType);

        $query = "UPDATE xcom_activity_chain_type SET name = :name, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[3] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
    }

    private function validateActivityChainType(array $submit): void {
        // If an Activity Chain Type ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'activity_chain_type', false, "Activity Chain Type ID");
        }

        // Name
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Activity Chain Type Name");

        // Enabled
        $this->enabled = Validate::testTF($submit['enabled'], false, "Activity Chain Type Enabled");

        // Notes
        $this->notes = Validate::testString($submit['notes'], -1, -1, true, "Activity Chain Type Notes");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[2] = array("param" => ":notes", "var" => $this->notes ?? null, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editActivityChainType($submit);
            } else {
                return Error::returnError("Covert Chain Type ID is set, but Covert Chain Type ID is not numeric");
            }
        } else {
            $this->newActivityChainType($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getActivityChainTypeForm(ActivityChainType $type) {
        if(!empty($type->id) and is_numeric($type->id)) {
            echo FieldHidden::getField('id',strval($type->id));
        }

        echo FieldText::getField('name', $type->name, 'form-control', 'name',
            "Name", array('col-md-4', 'pb-2', 'form-floating'), true);

        echo FieldSelect::GetField('enabled', strval($type->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-2','pb-2','form-floating'), true, Definitions::arrayYesNo());

        echo FieldTextarea::getField('notes', $type->notes, 'form-control', 'notes',
            'Notes', array('col-md-6','pb-2','form-floating'), false, '3');
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_activity_chain_type ORDER BY enabled DESC, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table info-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-6">Name</div>'."\n";
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
            $listString .= '<div class="col-6">';
            $listString .= '<a href="/covert/chain-type.php?id='.$row['id'].'">'.$row['name'].'</a>';
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

    public static function getActivityChainTypeArray(): array {
        $query = "SELECT * FROM xcom_activity_chain_type WHERE enabled = true ORDER BY name";
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