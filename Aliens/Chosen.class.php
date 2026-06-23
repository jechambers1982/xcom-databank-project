<?php
declare(strict_types = 1);

namespace XCOMDatabank\Aliens;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class Chosen
{
	public ?int $id;
	public string $type;
	public string $name;
	public bool $defeated;
		
	function __construct() {
        $this->id = null;
		$this->type = "";
		$this->name = "";
		$this->defeated = false;
	}
		
	public function getChosen(int $id) {
        $query = "SELECT * FROM xcom_chosen WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->type = $row['type'];
		$this->name = $row['name'];
		$this->defeated = boolval($row['defeated']);
	}
		
	public function editChosen($chosen) {
        $this->validateChosen($chosen);

        $query = "UPDATE xcom_chosen SET type = :type, name = :name, defeated = :defeated WHERE id = :id";
        $params[0] = array("param" => ":type", "var" => $this->type, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":defeated", "var" => $this->defeated, "type" => PDO::PARAM_BOOL,);
        $params[3] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateChosen(array $submit) : void {
        // Make sure Chosen ID is valid
        $this->id = Validate::testIndex(trim($submit['id']), 'chosen', false, "Chosen ID");

        // Make sure chosen type is valid
        $this->type = Validate::testArray(trim($submit['type']), Definitions::getChosen(), false, "Chosen Type");

        // Make sure name is a valid string
        $this->name = Validate::testString(trim($submit['name']), -1, -1, false, "Chosen Name");

        // Make sure defeated is some form of boolean value
        $this->defeated = Validate::testTF(trim($submit['defeated']), false, "Chosen Defeated?");
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editChosen($submit);
            } else {
                return Error::returnError("Chosen ID is set, but Chosen ID is not numeric");
            }
        } else {
            return Error::returnError("Chosen ID is not set - Should not be creating a new Chosen");
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getChosenForm(Chosen $chosen) : void {
        echo FieldHidden::getField('id',strval($chosen->id));

        // Get Chosen Type Field
        echo FieldSelect::getField('type', $chosen->type, 'form-control', 'type',
            'Chosen Type', array('col-md-4','pb-2','form-floating'), true, Definitions::getChosen());

        // Chosen Name Field
        echo FieldText::getField('name', $chosen->name, 'form-control', 'name',
            'Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        // Chosen Defeated Field
        echo FieldSelect::getField('defeated', strval($chosen->defeated), 'form-control', 'defeated',
            'Defeated?', array('col-md-2','pb-2','form-floating'), true, Definitions::arrayYesNo());
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_chosen ORDER BY defeated, type";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table chosen">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Chosen Type</div>'."\n";
        $listString .= '<div class="col-6">Chosen Name</div>'."\n";
        $listString .= '<div class="col-2">Defeated?</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {

            if($row['defeated']) {
                $rowClass = "row-red";
            } else {
                $rowClass = "row-green";
            }

            $listString .= '<div class="row '.$rowClass.'">'."\n";

            $listString .= '<div class="col-4">';
            $listString .= '<a href="/aliens/chosen.php?id='.$row['id'].'">'.$row['type'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-6">';
            $listString .= $row['name'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';

            if($row['defeated']) {
                $listString .= "Active";
            } else {
                $listString .=  "Defeated";
            }

            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getChosenArray(): array {
        $query = "SELECT id, type FROM xcom_chosen ORDER BY type";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $typeArray = array(
            array(
                'text' => "N/A",
                'value' => '',
            ),
        );

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['type'],
                'value' => $item['id'],
            );
            array_push($typeArray, $newArray);
        }

        return $typeArray;
    }
}