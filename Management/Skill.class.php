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

class Skill
{
	public ?int $id;
	public string $name;
	public string $description;
	public ?string $icon;
    public bool $enabled;
    public ?string $notes;
		
	function __construct() {
        $this->id = null;
		$this->name = "";
		$this->description = "";
		$this->icon = null;
        $this->enabled = true;
        $this->notes = null;
	}
		
	public function newSkill($skill) {
        $this->validateSkill($skill);

        $query = "INSERT INTO xcom_skills VALUES (NULL, :name, :description, :icon, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getSkill(int $id) {
        $query = "SELECT * FROM xcom_skills WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->name = $row['name'];
		$this->description = $row['description'];
		$this->icon = $row['icon'];
		$this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
	}
		
	public function editSkill($skill) {
        $this->validateSkill($skill);

        $query = "UPDATE xcom_skills SET name = :name, description = :description, icon = :icon, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[5] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateSkill(array $submit): void {
        // If a Skill ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'skills', false, "Skill ID");
        }

        // Make sure name is valid
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Skill Name");

        // Make description is valid
        $this->description = Validate::testString($submit['description'], -1, -1, false, "Skill Description");

        // Make sure icon is valid or null
        $this->icon = Validate::testImage($submit['icon'], 'skill', $this->name, true, "Skill Image", intval($submit['id'] ?? 0));

        // Make sure enabled is valid
        $this->enabled = Validate::testTF($submit['enabled'], false, "Skill Enabled");

        // Make sure notes is a string or null
        $this->notes = Validate::testString($submit['notes'], -1, -1, true, "Skill Notes");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":description", "var" => $this->description, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":icon", "var" => $this->icon ?? null, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[4] = array("param" => ":notes", "var" => $this->notes ?? null, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editSkill($submit);
            } else {
                return Error::returnError("Skill ID is set, but Skill ID is not numeric");
            }
        } else {
            $this->newSkill($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getSkillForm(Skill $skill)
    {
        if (!empty($skill->id) and is_numeric($skill->id)) {
            echo FieldHidden::getField('id', strval($skill->id));
        }

        echo FieldText::getField('name', $skill->name, 'form-control', 'name',
            'Skill Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldText::getField('description', $skill->description, 'form-control', 'description',
            'Description', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldSelect::getField('enabled', $skill->enabled ? "1" : "0", 'form-control', 'enabled',
            'Enabled?', array('col-md-2', 'pb-2', 'form-floating'), false, Definitions::arrayYesNo());

        echo FieldTextarea::getField('notes', $skill->notes, 'form-control', 'notes',
            'Notes', array('col-md-4', 'pb-2', 'form-floating'), false, '5');

        if(!empty($skill->icon)) {
            echo FieldHidden::getField('icon_current', strval($skill->icon));
        }

        echo FieldFile::getField('icon', strval($skill->icon), 'icon', 'form-control', false, 'jpg, gif, jpeg, png',
            'Icon', array('col-md-6', 'pb-2', 'form-floating'));
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_skills ORDER BY enabled DESC, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-2">Icon</div>'."\n";
        $listString .= '<div class="col-4">Name</div>'."\n";
        $listString .= '<div class="col-2">Enabled?</div>'."\n";
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
            $listString .= '<img src="https://xcom-databank.games/'.$row['icon'].'" alt="Rank Icon" width="32">';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= '<a href="/management/skills.php?id='.$row['id'].'">'.$row['name'].'</a>';
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

    public static function getSkillsArray(): array {
        $query = "SELECT * FROM xcom_skills WHERE enabled = true ORDER BY name";
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