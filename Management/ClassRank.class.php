<?php
declare(strict_types = 1);

namespace XCOMDatabank\Management;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class ClassRank
{
	public ?int $id;
	public int $classID;
	public int $rankID;
    public bool $enabled;
		
	function __construct() {
        $this->id = null;
		$this->classID = -1;
		$this->rankID = -1;
        $this->enabled = false;
	}
		
	public function newClassRank($classRank) {
        $this->validateClassRank($classRank);

        $query = "INSERT INTO xcom_class_rank VALUES (NULL, :classID, :rankID, :enabled)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getClassRank(int $id) {
        $query = "SELECT * FROM xcom_class_rank WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->classID = intval($row['class_id']);
		$this->rankID = intval($row['rank_id']);
		$this->enabled = boolval($row['enabled']);
	}
		
	public function editClassRank($classRank) {
        $this->validateClassRank($classRank);

        $query = "UPDATE xcom_class_rank SET class_id = :classID, rank_id = :rankID, enabled = :enabled WHERE id = :id";
        $params = $this->getParams();
        $params[3] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateClassRank(array $submit): void {
        // If a ClassRank ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'class_rank', false, "Class Rank ID");
        }

        // Make sure Class ID is valid
        $this->classID = Validate::testIndex($submit['class_id'], 'class', false, "ClassRank Class ID");

        // Make sure Rank ID is valid
        $this->rankID = Validate::testIndex($submit['rank_id'], 'rank', false, "ClassRank Rank ID");

        // Make sure enabled is valid
        $this->enabled = Validate::testTF($submit['enabled'], false, "ClassRank Enabled");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":classID", "var" => $this->classID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":rankID", "var" => $this->rankID, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editClassRank($submit);
            } else {
                return Error::returnError("Skill ID is set, but Skill ID is not numeric");
            }
        } else {
            $this->newclassRank($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getClassRankForm(ClassRank $classRank)
    {
        if (!empty($classRank->id) and is_numeric($classRank->id)) {
            echo FieldHidden::getField('id', strval($classRank->id));
        }

        echo FieldSelect::getField('class_id', strval($classRank->classID), 'form-control', 'class-id',
            'Class', array('col-md-4', 'pb-2', 'form-floating'), true, SoldierClass::getClassArrayAll());

        echo FieldSelect::getField('rank_id', strval($classRank->rankID), 'form-control', 'rank-id',
            'Rank', array('col-md-4', 'pb-2', 'form-floating'), true, Rank::getRankArrayAll());

        echo FieldSelect::getField('enabled', $classRank->enabled ? "1" : "0", 'form-control', 'enabled',
            'Enabled?', array('col-md-4', 'pb-2', 'form-floating'), true, Definitions::arrayYesNo());
    }

    public static function getListPage(): void {
        $query = "SELECT cr.id, class.name as class, xrank.level as level, xrank.name as xrank, cr.enabled as enabled 
			FROM xcom_class_rank as cr 
				INNER JOIN xcom_class as class ON cr.class_id = class.id 
				INNER JOIN xcom_rank as xrank ON cr.rank_id = xrank.id 
			ORDER by cr.enabled DESC, class.name, xrank.level, xrank.name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-2">Edit</div>'."\n";
        $listString .= '<div class="col-3">Class</div>'."\n";
        $listString .= '<div class="col-3">Rank</div>'."\n";
        $listString .= '<div class="col-2">Level</div>'."\n";
        $listString .= '<div class="col-2">Enabled</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            if($row['enabled'] == 1) {
                $enabledClass = "row-green";
            } else {
                $enabledClass = "row-red";
            }

            $listString .= '<div class="row '.$enabledClass.'">'."\n";
            $listString .= '<div class="col-2">';
            $listString .= '<a href="/management/class-rank.php?id='.$row['id'].'">[Edit]</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $row['class'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $row['xrank'];
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