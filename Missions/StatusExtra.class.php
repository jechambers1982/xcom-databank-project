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

class StatusExtra
{
    public ?int $id;
    public string $value;
    public bool $enabled;
    public ?string $notes;

    function __construct() {
        $this->id = null;
        $this->value = "";
        $this->enabled = true;
        $this->notes = "";
    }

    public function newStatusExtra($extra): void {

        $this->validateExtra($extra);

        $query = "INSERT INTO xcom_status_extra VALUES (NULL, :value, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
    }

    public function getStatusExtra(int $id): void {
        $query = "SELECT * FROM xcom_status_extra WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

        $this->id = $id;
        $this->value = $row['value'];
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
    }

    public function editStatusExtra($extra): void {
        $this->validateExtra($extra);

        $query = "UPDATE xcom_status_extra SET value = :value, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[3] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
    }

    private function validateExtra(array $submit): void {
        // If an Objective ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'status_extra', false, "Extra ID");
        }

        // Make sure value is a valid, non-null string
        $this->value = Validate::testString($submit['value'], -1, -1, false, "Extra Value");

        // Make sure enabled is some form of boolean value
        $this->enabled = Validate::testTF($submit['enabled'], false, "Extra Enabled");

        // Make sure notes is a valid string or null
        $this->notes = Validate::testString($submit['notes'] ?? null, -1, -1, true, "Extra Notes");
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editStatusExtra($submit);
            } else {
                return Error::returnError("Extra ID is set, but Extra ID is not numeric");
            }
        } else {
            $this->newStatusExtra($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getExtraForm(StatusExtra $extra) {
        $id = $extra->id;
        if($id != "" and $id !== null and is_numeric($id)) {
            echo FieldHidden::getField('id',strval($id));
        }

        // Print Extra Value Form Field
        echo FieldText::getField('value', $extra->value, 'form-control', 'value',
            'Extra Value', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldSelect::getField('enabled', strval($extra->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-2','pb-2','form-floating'), true, Definitions::arrayYesNo());

        // Print Mission Types Notes Field
        echo FieldText::getField('notes', $extra->notes, 'form-control', 'notes',
            'Objective Notes', array('col-md-7','pb-2','form-floating'), false);
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_status_extra ORDER BY enabled DESC, value DESC";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table status-extra">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Extra Value</div>'."\n";
        $listString .= '<div class="col-2">Enabled?</div>'."\n";
        $listString .= '<div class="col-6">Notes</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            if($row['enabled'] == 1) {
                $enabledClass = "row-green";
            } else {
                $enabledClass = "row-red";
            }

            $listString .= '<div class="row '.$enabledClass.'">'."\n";

            $listString .= '<div class="col-4">';
            $listString .= '<a href="/mission/extra.php?id='.$row['id'].'">'.$row['value'].'</a>';
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

    public static function getExtraArray(): array {
        $query = "SELECT * FROM xcom_status_extra WHERE enabled = true ORDER BY value";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $typeArray = array(
            array(
                'text' => "Select Extra",
                'value' => '',
            ),
        );

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['value'],
                'value' => $item['id'],
            );
            array_push($typeArray, $newArray);
        }

        return $typeArray;
    }

    private function getParams(): array {
        $params[0] = array("param" => ":value", "var" => $this->value, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[2] = array("param" => ":notes", "var" => $this->notes, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public static function getExtraStringByID(?int $id): ?string {
        if($id === null) {
            return null;
        } else {
            $extra = new StatusExtra;
            $extra->getStatusExtra(intval($id));
            return $extra->value;
        }
    }
}