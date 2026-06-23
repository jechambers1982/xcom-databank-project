<?php
declare(strict_types = 1);

namespace XCOMDatabank\Management;

use PDO;
use XCOMDatabank\Forms\FieldFile;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextarea;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class Rank
{
	public ?int $id;
	public string $name;
    public string $short;
	public int $level;
	public ?string $icon;
    public bool $enabled;
    public ?string $notes;
		
	function __construct() {
        $this->id = null;
		$this->name = "";
        $this->short = "";
		$this->level = -1;
		$this->icon = null;
        $this->enabled = false;
        $this->notes = null;
	}
		
	public function newRank($rank) {
        $this->validateRank($rank);

        $query = "INSERT INTO xcom_rank VALUES (NULL, :name, :short, :level, :icon, :enabled, :notes)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getRank(int $id) {
        $query = "SELECT * FROM xcom_rank WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

		$this->id = $id;
		$this->name = $row['name'];
        $this->short = $row['short'];
		$this->level = intval($row['level']);
		$this->icon = $row['icon'];
        $this->enabled = boolval($row['enabled']);
        $this->notes = $row['notes'];
	}
		
	public function editRank($rank) {
        $this->validateRank($rank);

        $query = "UPDATE xcom_rank SET name = :name, short = :short, level = :level, icon = :icon, enabled = :enabled, notes = :notes WHERE id = :id";
        $params = $this->getParams();
        $params[6] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateRank(array $submit): void {
        // If a Rank ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'rank', false, "Info ID");
        }

        // Make sure name is valid
        $this->name = Validate::testString($submit['name'], -1, -1, false, "Rank Name");

        // Make sure short name is valid
        $this->short = Validate::testString($submit['short'], 3, 4, false, "Rank Short Name");

        // Make sure level is valid
        $this->level = Validate::testInteger(intval($submit['level']), 1, 9, false, "Rank Level");

        // Make sure icon is valid or null
        $this->icon = Validate::testImage($submit['icon'], 'rank', $this->name, true, "Rank Image");

        // Make sure enabled is valid
        $this->enabled = Validate::testTF($submit['enabled'], false, "Rank Enabled");

        // Make sure notes is a string or null
        $this->notes = Validate::testString($submit['notes'], -1, -1, true, "Rank Notes");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":name", "var" => $this->name, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":short", "var" => $this->short, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":level", "var" => $this->level, "type" => PDO::PARAM_INT,);
        $params[3] = array("param" => ":icon", "var" => $this->icon ?? null, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":enabled", "var" => $this->enabled, "type" => PDO::PARAM_BOOL,);
        $params[5] = array("param" => ":notes", "var" => $this->notes ?? null, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editRank($submit);
            } else {
                return Error::returnError("Rank ID is set, but Rank ID is not numeric");
            }
        } else {
            $this->newRank($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getRankForm(Rank $rank) {
        if(!empty($rank->id) and is_numeric($rank->id)) {
            echo FieldHidden::getField('id',strval($rank->id));
        }

        echo FieldText::getField('name', $rank->name, 'form-control', 'name',
            'Rank Name', array('col-md-3', 'pb-2', 'form-floating'), true);

        echo FieldText::getField('short', $rank->short, 'form-control', 'short',
            'Short', array('col-md-3','pb-2','form-floating'), true);

        echo FieldTextNum::getField('level', strval($rank->level), 'form-control', 'short',
            "Level", array('col-md-3', 'pb-2', 'form-floating'), true, false,"1", "8");

        echo FieldSelect::getField('enabled', $rank->enabled ? "1" : "0", 'form-control', 'enabled',
            'Enabled?', array('col-md-3', 'pb-2', 'form-floating'), false, Definitions::arrayYesNo());

        echo FieldTextarea::getField('notes', $rank->notes, 'form-control', 'notes',
            'Notes', array('col-md-6','pb-2','form-floating'), false, '5');

        if(!empty($rank->icon)) {
            echo FieldHidden::getField('icon_current', strval($rank->icon));
        }

        echo FieldFile::getField('icon', 'https://xcom-databank.games'.$rank->icon, 'icon', 'form-control', false, 'jpg, gif, jpeg, png',
            'Icon', array('col-md-6','pb-2','form-floating'));
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_rank ORDER BY enabled DESC, level DESC, name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-2">Icon</div>'."\n";
        $listString .= '<div class="col-3">Name</div>'."\n";
        $listString .= '<div class="col-2">Short</div>'."\n";
        $listString .= '<div class="col-2">Level</div>'."\n";
        $listString .= '<div class="col-3">Enabled</div>'."\n";
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

            $listString .= '<div class="col-3">';
            $listString .= '<a href="/management/rank.php?id='.$row['id'].'">'.$row['name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['short'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['level'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $row['enabled'] ? "Yes" : "No";
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getRankArray(): array {
        $query = "SELECT * FROM xcom_rank WHERE enabled = true ORDER BY level, name";
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

    public static function getRankArrayAll(): array {
        $query = "SELECT * FROM xcom_rank ORDER BY enabled DESC, level, name";
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

    public static function getRanksByClass(int $classID): array {
        $query = "SELECT xrank.name as name, xrank.id as id FROM xcom_rank as xrank
                        INNER JOIN xcom_class_rank as cr ON cr.rank_id = xrank.id 
                    WHERE cr.class_id = :classID and xrank.enabled = 1
                    ORDER BY xrank.level";
        $params[0] = array("param" => ":classID", "var" => $classID, "type" => PDO::PARAM_INT,);
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