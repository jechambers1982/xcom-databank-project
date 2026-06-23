<?php
declare(strict_types = 1);

namespace XCOMDatabank\Soldiers;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Management\Skill;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class SoldierSkill
{
	public ?int $id;
	public int $soldierID;
	public int $skillID;
		
	function __construct() {
        $this->id = null;
		$this->soldierID = -1;
		$this->skillID = -1;
	}
		
	public function newSoldierSkill($soldierSkill) {
        $this->validateSoldierSkill($soldierSkill);

        $query = "INSERT INTO xcom_soldier_skills VALUES (NULL, :soldierID, :skillID)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
	
	public function deleteSoldierSkill($soldierSkill) {	
		$query = "DELETE FROM xcom_soldier_skills WHERE id = :id";
		$params[0] = array("param" => ":id", "var" => $soldierSkill, "type" => PDO::PARAM_INT,);
		
		$queryResult = Database::runQuery('delete', $query, $params);
	}
		
	public function getSoldierSkill(int $id) {
        $query = "SELECT * FROM xcom_soldier_skills WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->soldierID = intval($row['soldier_id']);
		$this->skillID = intval($row['skill_id']);
	}
		
	public function editSoldierSkill($soldierSkill) {
        $this->validateSoldierSkill($soldierSkill);

        $query = "UPDATE xcom_soldier_skills SET soldier_id = :soldierID, skills_id = :skillID WHERE id = :id";
        $params = $this->getParams();
        $params[2] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateSoldierSkill(array $submit): void {
        // If a SoldierSkill ID was submitted, make sure it is valid
        if(isset($submit['soldierSkill_id'])) {
            $this->id = Validate::testIndex($submit['soldierSkill_id'], 'soldier_skills', false, "SoldierSkill ID");
        }

        // Make sure soldier ID is valid
        $this->soldierID = Validate::testIndex($submit['soldier_id'], 'soldier', false, "SoldierSkill Soldier ID");

        // Make sure Skill ID is valid
        $this->skillID = Validate::testIndex($submit['skill_id'], 'skills', false, "SoldierSkill Skill ID");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":soldierID", "var" => $this->soldierID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":skillID", "var" => $this->skillID, "type" => PDO::PARAM_INT,);
        return $params;
    }

    public function processForm(array $submit, string $redirect = ''): string {
        // We only care if it's not set, because if it is set, then it already exists. There is no removing it
        if(!isset($submit['soldierSkill_id'])) {
            $this->newSoldierSkill($submit);
        }
        if(!empty($redirect)) {
            header('Location: '.$redirect);
        }
        return "";
    }

    public static function getSoldierSkillForm(SoldierSkill $soldierSkill)
    {
        if (!empty($soldierSkill->id) and is_numeric($soldierSkill->id)) {
            echo FieldHidden::getField('id', strval($soldierSkill->id));
        }

        echo FieldSelect::getField('soldier_id', strval($soldierSkill->soldierID), 'form-control', 'class-id',
            'soldier', array('col-md-4', 'pb-2', 'form-floating'), true, Soldier::getAvailableSoldierArray());

        echo FieldSelect::getField('skill_id', strval($soldierSkill->skillID), 'form-control', 'skill-id',
            'Skill', array('col-md-4', 'pb-2', 'form-floating'), true, Skill::getSkillsArray());
    }

    public static function getListPage(): void {
        $query = "SELECT ss.id, soldier.first_name, soldier.last_name, soldier.nickname, skill.name
			FROM xcom_soldier_skills as ss 
				INNER JOIN xcom_soldier as soldier ON ss.soldier_id = soldier.id
				INNER JOIN xcom_skills as skill ON ss.skills_id = skill.id
			ORDER BY soldier.last_name, skill.name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-2">Edit</div>'."\n";
        $listString .= '<div class="col-5">Soldier</div>'."\n";
        $listString .= '<div class="col-5">Skill</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            $listString .= '<div class="row">'."\n";
            $listString .= '<div class="col-2">';
            $listString .= '<a href="/soldier/soldier-skill.php?id='.$row['id'].'">[Edit]</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-5">';
            $listString .= $row['first_name'].' "'.$row['nickname'].'" '.$row['last_name'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-5">';
            $listString .= $row['name'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getSoldierSkillIDbySkill(int $skill, int $soldier): ?int {
        $query = "SELECT id FROM xcom_soldier_skills WHERE skills_id = :skillID and soldier_id = :soldierID";
        $params[0] = array("param" => ":skillID", "var" => $skill, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":soldierID", "var" => $soldier, "type" => PDO::PARAM_INT,);
        $queryResult = Database::runQuery('select', $query, $params);

        if ($queryResult->rowCount() > 0) {
            $row = $queryResult->fetch();
            return (int)$row['id'];
        } else {
            return null;
        }
    }
}
