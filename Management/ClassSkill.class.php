<?php
declare(strict_types = 1);

namespace XCOMDatabank\Management;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class ClassSkill
{
	public ?int $id;
	public int $classID;
	public int $skillID;
    public int $rankLevel;
    public bool $enabled;
		
	function __construct() {
        $this->id = null;
		$this->classID = -1;
		$this->skillID = -1;
        $this->rankLevel = -1;
        $this->enabled = true;
	}
		
	public function newClassSkill($classSkill) {
        $this->validateClassSkill($classSkill);

        $query = "INSERT INTO xcom_class_skill VALUES (NULL, :classID, :skillID, :rankLevel, :enabled)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getClassSkill(int $id) {
        $query = "SELECT * FROM xcom_class_skill WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->classID = intval($row['class_id']);
		$this->skillID = intval($row['skill_id']);
		$this->rankLevel = intval($row['rank_level']);
        $this->enabled = boolval($row['enabled']);
	}
		
	public function editClassSkill($classSkill) {
        $this->validateClassSkill($classSkill);

        $query = "UPDATE xcom_class_skill SET class_id = :classID, skill_id = :skillID, rank_level = :rankLevel, enabled = :enabled WHERE id = :id";
        $params = $this->getParams();
        $params[4] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateClassSkill(array $submit): void {
        // If a ClassSkill ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'class_skill', false, "Class Skill ID");
        }

        // Make sure Class ID is valid
        $this->classID = Validate::testIndex($submit['class_id'], 'class', false, "ClassSkill Class ID");

        // Make sure Rank ID is valid
        $this->skillID = Validate::testIndex($submit['skill_id'], 'skills', false, "ClassSkill Skill ID");

        // Make sure Rank level is an integer
        $this->rankLevel = Validate::testInteger($submit['rank_level'], 1, 20, false, "ClassSkill Rank Level");

        // Make sure enabled is valid
        $this->enabled = Validate::testTF($submit['enabled'], false, "ClassSkill Enabled");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":classID", "var" => $this->classID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":skillID", "var" => $this->skillID, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":rankLevel", "var" => $this->rankLevel, "type" => PDO::PARAM_INT,);
        $params[3] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editClassSkill($submit);
            } else {
                return Error::returnError("ClassSkill ID is set, but ClassSkill ID is not numeric");
            }
        } else {
            $this->newClassSkill($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getClassSkillForm(ClassSkill $classSkill)
    {
        if (!empty($classSkill->id) and is_numeric($classSkill->id)) {
            echo FieldHidden::getField('id', strval($classSkill->id));
        }

        echo FieldSelect::getField('class_id', strval($classSkill->classID), 'form-control', 'class-id',
            'Class', array('col-md-3', 'pb-2', 'form-floating'), true, SoldierClass::getClassArray());

        echo FieldSelect::getField('skill_id', strval($classSkill->skillID), 'form-control', 'skill-id',
            'Skill', array('col-md-3', 'pb-2', 'form-floating'), true, Skill::getSkillsArray());

        echo FieldTextNum::getField('rank_level', strval($classSkill->rankLevel), 'form-control', 'rank-level',
            "Rank Level", array('col-md-3', 'pb-2', 'form-floating'), true, false, "1", "20");

        echo FieldSelect::getField('enabled', strval($classSkill->enabled), 'form-control', 'enabled',
            'Enabled?', array('col-md-3', 'pb-2', 'form-floating'), true, Definitions::arrayYesNo());
    }

    public static function getListPage(): void {
        $query = "SELECT cs.id, class.name class, skill.name skill, cs.rank_level as level, cs.enabled as enabled 
			FROM xcom_class_skill as cs 
				INNER JOIN xcom_class as class ON cs.class_id = class.id 
				INNER JOIN xcom_skills as skill ON cs.skill_id = skill.id 
			ORDER by cs.enabled DESC, class.name, skill.name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-2">Edit</div>'."\n";
        $listString .= '<div class="col-3">Class</div>'."\n";
        $listString .= '<div class="col-3">Skill</div>'."\n";
        $listString .= '<div class="col-2">Level</div>'."\n";
        $listString .= '<div class="col-2">Enabled</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            $listString .= '<div class="row">'."\n";
            $listString .= '<div class="col-2">';
            $listString .= '<a href="/management/class-skill.php?id='.$row['id'].'">[Edit]</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $row['class'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $row['skill'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['level'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['enabled'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }
}