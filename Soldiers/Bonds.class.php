<?php
declare(strict_types = 1);

namespace XCOMDatabank\Soldiers;

use PDO;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class Bonds
{
	public ?int $id;
	public int $soldierOne;
	public int $soldierTwo;
	public int $bondLevel;
	public bool $active;
		
	function __construct() {
        $this->id = null;
		$this->soldierOne = -1;
		$this->soldierTwo = -1;
		$this->bondLevel = 0;
		$this->active = false;
	}
		
	public function newBond($bond) {
        $this->validateBond($bond);

        $query = "INSERT INTO xcom_bonds VALUES (NULL, :soldierOne, :soldierTwo, :bondLevel, :active)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getBond(int $id) {
        $query = "SELECT * FROM xcom_bonds WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->soldierOne = intval($row['soldier_id1']);
		$this->soldierTwo = intval($row['soldier_id2']);
		$this->bondLevel = intval($row['bond_level']);
		$this->active = boolval($row['active']);
	}
		
	public function editBond($bond) {
        $this->validateBond($bond);

        $query = "UPDATE xcom_bonds SET soldier_id1 = :soldierOne, soldier_id2 = :soldierTwo, bond_level = :bondLevel, active = :active WHERE id = :id";
        $params = $this->getParams();
        $params[4] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateBond(array $submit): void {
        // If a Bond ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'bonds', false, "Bond ID");
        }

        // Make sure soldier1 ID is valid
        $this->soldierOne = Validate::testIndex($submit['soldier_id1'], 'soldier', false, "Bond Soldier 1 ID");

        // Make sure soldier2 ID is valid
        $this->soldierTwo = Validate::testIndex($submit['soldier_id2'], 'soldier', false, "Bond Soldier 2 ID");

        // Make sure bond level is valid
        $this->bondLevel = Validate::testInteger((int)$submit['bond_level'], 1, 3, false, "Bond Level");

        // Make sure bond active is valid
        $this->active = Validate::testTF($submit['active'], false, "Bond Active");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":soldierOne", "var" => $this->soldierOne, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":soldierTwo", "var" => $this->soldierTwo, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":bondLevel", "var" => $this->bondLevel, "type" => PDO::PARAM_INT,);
        $params[3] = array("param" => ":active", "var" => $this->active, "type" => PDO::PARAM_BOOL,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editBond($submit);
            } else {
                return Error::returnError("Bond ID is set, but Bond ID is not numeric");
            }
        } else {
            $this->newBond($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getBondForm(Bonds $bond)
    {
        if (!empty($bond->id) and is_numeric($bond->id)) {
            echo FieldHidden::getField('id', strval($bond->id));
        }

        echo FieldSelect::getField('soldier_id1', strval($bond->soldierOne), 'form-control', 'soldier-id1',
            'Soldier 1', array('col-md-3', 'pb-2', 'form-floating'), true, Soldier::getSoldierArray());

        echo FieldSelect::getField('soldier_id2', strval($bond->soldierTwo), 'form-control', 'soldier-id2',
            'Soldier 2', array('col-md-3', 'pb-2', 'form-floating'), true, Soldier::getSoldierArray());

        echo FieldTextNum::getField('bond_level', strval($bond->bondLevel), 'form-control', 'bond-level',
            'Level', array('col-md-3', 'pb-2', 'form-floating'), true, false,"1", "3");

        echo FieldSelect::getField('active', strval($bond->active), 'form-control', 'active',
            'Active', array('col-md-3', 'pb-2', 'form-floating'), true, array('True','False'));
    }

    public static function getListPage(): void {
        $query = "SELECT bonds.id, bonds.active, bonds.bond_level as level, soldier1.first_name as first1, soldier1.last_name as last1, soldier1.nickname as nickname1, soldier2.first_name as first2, soldier2.last_name as last2, soldier2.nickname as nickname2 
			FROM xcom_bonds as bonds 
				INNER JOIN xcom_soldier as soldier1 ON bonds.soldier_id1 = soldier1.id 
				INNER JOIN xcom_soldier as soldier2 ON bonds.soldier_id2 = soldier2.id 
			ORDER BY bonds.active desc, bonds.bond_level desc, soldier1.id, soldier2.id";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-2">Edit</div>'."\n";
        $listString .= '<div class="col-4">Soldier 1</div>'."\n";
        $listString .= '<div class="col-4">Soldier 2</div>'."\n";
        $listString .= '<div class="col-2">Bond Level</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            $rowStyle = 'active';
            if($row['active'] == 0) {
                $rowStyle = 'row-red';
            }

            $listString .= '<div class="row '.$rowStyle.'">'."\n";
            $listString .= '<div class="col-2">';
            $listString .= '<a href="/soldier/bonds.php?id='.$row['id'].'">[Edit]</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['first1'].' "'.$row['nickname1'].'" '.$row['last1'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['first2'].' "'.$row['nickname2'].'" '.$row['last2'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['level'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }
}