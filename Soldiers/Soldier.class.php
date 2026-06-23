<?php
declare(strict_types = 1);
namespace XCOMDatabank\Soldiers;
use PDO;
use XCOMDatabank\Forms\FieldCheckbox;
use XCOMDatabank\Forms\FieldFile;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Forms\FieldTextNumDate;
use XCOMDatabank\Management\Info;
use XCOMDatabank\Management\Rank;
use XCOMDatabank\Management\SoldierClass;
use XCOMDatabank\Missions\MissionSoldier;
use XCOMDatabank\Missions\StatusExtra;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;
class Soldier
{
	// Soldier ID for unique identification
	public ?int $id;
	// Foreign Keys
	public int $classID;
	public int $rankID;
	// Basic Soldier Information
	public ?string $firstName;
	public ?string $lastName;
	public ?string $nickname;
	public ?string $country;
	public ?string $photo;
	public int $gender;
    public string $joinDate;
	//Soldier Statistics
	public int $aim;
	public int $hack;
	public int $health;
	public int $dodge;
	public int $movement;
	public ?int $will;
	public ?int $psi;
	public ?string $intelligence;
	public int $killed;
	function __construct() {
        $this->id = null;
		$this->classID = -1;
		$this->rankID = -1;
		$this->firstName = "";
		$this->lastName = "";
		$this->nickname = null;
		$this->country = null;
		$this->photo = null;
		$this->gender = 0;
        $this->joinDate = Info::getCurrentDateShort();
		
		$this->aim = 0;
		$this->hack = 0;
		$this->health =0;
		$this->dodge = 0;
		$this->movement = 0;
		$this->will = null;
		$this->psi = null;
		$this->intelligence = null;
		
		$this->killed = 0;
	}
	
	public function newSoldier($soldier) {
        $this->validateSoldier($soldier);

        $query = "INSERT INTO xcom_soldier VALUES (NULL, :classID, :rankID, :firstName, :lastName, :nickname, :country,
                        :photo, :gender, :aim, :hack, :health, :dodge, :movement, :will, :psi, :intelligence, :killed, :joined)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
	
	public function getSoldier(int $id) {
        $query = "SELECT * FROM xcom_soldier WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

        $this->id = $id;
        $this->classID = intval($row['class_id']);
        $this->rankID = intval($row['rank_id']);
			
        $this->firstName = $row['first_name'];
        $this->lastName = $row['last_name'];
        $this->nickname = $row['nickname'];
        $this->country = $row['country'];
        $this->photo = $row['photo'];
        $this->gender = intval($row['gender']);
			
        $this->aim = intval($row['aim']);
        $this->dodge = intval($row['dodge']);
        $this->hack = intval($row['hack']);
        $this->will = intval($row['will']) ?: null;
        $this->movement = intval($row['movement']);
        $this->health = intval($row['health']);
        $this->psi = intval($row['psi']) ?: null;
        $this->intelligence = $row['intelligence'];
			
        $this->killed = intval($row['killed']);

        $this->joinDate = $row['date_joined'];
	}
	
	public function editSoldier($soldier)
	{
        $this->validateSoldier($soldier);

        $query = "UPDATE xcom_soldier SET class_id = :classID, rank_id = :rankID, first_name = :firstName, last_name = :lastName, 
                        nickname = :nickname, country = :country, photo = :photo, gender = :gender, aim = :aim, hack = :hack, 
                        health = :health, dodge = :dodge, movement = :movement, will = :will, psi = :psi, 
                        intelligence = :intelligence, killed = :killed, date_joined = :joined WHERE id = :id";
        $params = $this->getParams();
        $params[19] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateSoldier(array $submit): void {
        // If a Soldier ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex(intval($submit['id']), 'soldier', false, "Soldier ID");
        }

        // Make sure Class ID is valid
        $this->classID = Validate::testIndex(intval($submit['class_id']), 'class', false, "Soldier Class");

        // Make sure Rank ID is valid
        $this->rankID = Validate::testIndex(intval($submit['rank_id']), 'rank', false, "Soldier Rank");

        // Get First Name
        $this->firstName = Validate::testString($submit['first_name'] ?? null, 1, 15, true, "Soldier First Name");

        // Get Last Name
        $this->lastName = Validate::testString($submit['last_name'] ?? null, 1, 15, true, "Soldier Last Name");

        // Get Nickname
        $this->nickname = Validate::testString($submit['nickname'], 1, 20, true, "Soldier Nickname");

        $this->country = Validate::testArray($submit['country'] ?? null, Definitions::getCountry(), true, "Soldier Country");

        // Soldier Gender (0 for Male, 1 for Female)
        $this->gender = Validate::testInteger(intval($submit['gender']), 0, 1, false, "Soldier Gender");

        // Soldier Aim
        $this->aim = Validate::testInteger(intval($submit['aim']), 40, 200, false, "Soldier Aim");

        // Soldier Hack
        $this->hack = Validate::testInteger(intval($submit['hack']), 0, 250, false, "Soldier Hack");

        // Soldier Health
        $this->health = Validate::testInteger(intval($submit['health']), 2, 25, false, "Soldier Health");

        // Soldier Dodge
        $this->dodge = Validate::testInteger(intval($submit['dodge']), 0, 200, false, "Soldier Dodge");

        // Soldier Movement
        $this->movement = Validate::testInteger(intval($submit['movement']), 5, 30, false, "Soldier Movement");

        // Soldier Combat Intelligence
        $this->intelligence = Validate::testArray($submit['intelligence'] ?? null, Definitions::getIntelligence(), true, "Soldier Combat Intelligence");

        // Check Whether Soldier is killed or not
        $this->killed = Validate::testInteger(intval($submit['killed']), 0, 2, false, "Soldier Killed Status");

        // Soldier Will
        $this->will = Validate::testInteger($submit['will'] ?? null, 30, 200, true, "Soldier Will");

        // Soldier Psi
        $this->psi = Validate::testInteger($submit['psi'] ?? null, 0, 200, true, "Soldier Psi");

        // Join Date
        $this->joinDate = Validate::testDate($submit['date_joined'] ?? $this->joinDate, false, "Joined Date");

        // Picture Handling
        /* There are Five cases we need to deal with:
            1) There is no current picture (e.g. empty($this->picture) is true) and a New picture is being added. This
                should always be the case on mission completion
            2) A picture is set, Deleted is set, but a new picture is not set. This should result in $this->picture being cleared
            3) A picture is set, Deleted is set, and a new picture is being uploaded. Should replace $this->picture with new picture
            4) A picture is set, a new picture is selected, but Deleted IS NOT set. New picture should be ignored
            5) There is no current picture and no new image is being added - keep $this->picture empty
        */
        if(empty($this->photo)) { // If $this->picture is Empty. First step for cases 1 and 5 above
            if(!empty($submit['picture'])) { // If picture is being submitted, do step 1. Else, do nothing
                $this->photo = Validate::testImage($submit['picture'], 'soldier', $this->lastName.'-'.$this->firstName, true, "Soldier Image");
            }
        } else { // $this->picture is NOT empty. First step for cases 2, 3, and 4 above
            if(!empty($submit['delete'])) { // If Deleted tag is set, prepare to do either steps 2 or 3. Else, do nothing
                if(empty($submit['picture'])) { // If no new image is being uploaded, set $this->picture to null
                    $this->photo = null;
                } else { // Otherwise, Do Case 3
                    $this->photo = Validate::testImage($submit['picture'], 'soldier', $this->lastName.'-'.$this->firstName.'-'.$submit['id'], true, "Soldier Image");
                }
            }
        }
    }

    private function getParams(): array {
        $params[0] = array("param" => ":classID", "var" => $this->classID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":rankID", "var" => $this->rankID, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":firstName", "var" => $this->firstName, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":lastName", "var" => $this->lastName, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":gender", "var" => $this->gender, "type" => PDO::PARAM_BOOL,);
        $params[5] = array("param" => ":aim", "var" => $this->aim, "type" => PDO::PARAM_INT,);
        $params[6] = array("param" => ":hack", "var" => $this->hack, "type" => PDO::PARAM_INT,);
        $params[7] = array("param" => ":health", "var" => $this->health, "type" => PDO::PARAM_INT,);
        $params[8] = array("param" => ":dodge", "var" => $this->dodge, "type" => PDO::PARAM_INT,);
        $params[9] = array("param" => ":movement", "var" => $this->movement, "type" => PDO::PARAM_INT,);
        $params[10] = array("param" => ":killed", "var" => $this->killed, "type" => PDO::PARAM_INT,);
        $params[11] = array("param" => ":nickname", "var" => $this->nickname, "type" => PDO::PARAM_STR,);
        $params[12] = array("param" => ":country", "var" => $this->country, "type" => PDO::PARAM_STR,);
        $params[13] = array("param" => ":photo", "var" => $this->photo, "type" => PDO::PARAM_STR,);
        $params[14] = array("param" => ":will", "var" => $this->will, "type" => PDO::PARAM_INT,);
        $params[15] = array("param" => ":psi", "var" => $this->psi, "type" => PDO::PARAM_INT,);
        $params[16] = array("param" => ":intelligence", "var" => $this->intelligence, "type" => PDO::PARAM_STR,);
        $params[17] = array("param" => ":joined", "var" => $this->joinDate, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect = ''): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editSoldier($submit);
            } else {
                return Error::returnError("Soldier ID is set, but Soldier ID is not numeric");
            }
        } else {
            $this->newSoldier($submit);
        }
        if(!empty($redirect)) {
            header('Location: '.$redirect);
        }
        return "";
    }

    public static function getSoldierForm(Soldier $soldier)
    {
        if (!empty($soldier->id) and is_numeric($soldier->id)) {
            echo FieldHidden::getField('id', strval($soldier->id));
        }

        // Check is Class is Hero class, if so, set to disable some fields
        $countryDisabled = false;
        $psiDisabled = false;
        $willDisabled = false;
        if($soldier->classID == SoldierClass::getClassIDByName("Skirmisher") or
            $soldier->classID == SoldierClass::getClassIDByName("Reaper") or
            $soldier->classID == SoldierClass::getClassIDByName("Templar")) {
            $countryDisabled = true;
        }

        // Check if Class is a SPARK class, if so, set to disable some fields
        if($soldier->classID == SoldierClass::getClassIDByName("SPARK") or
            $soldier->classID == SoldierClass::getClassIDByName("Spark: Artillery") or
            $soldier->classID == SoldierClass::getClassIDByName("Spark: Infiltrator") or
            $soldier->classID == SoldierClass::getClassIDByName("Spark: Pioneer")) {
            $countryDisabled = true;
            $psiDisabled = true;
            $willDisabled = true;
        }

        echo FieldText::getField('first_name', $soldier->firstName, 'form-control', 'first-name',
            'First Name', array('col-md-4', 'pb-2', 'form-floating'), false);

        echo FieldText::getField('nickname', $soldier->nickname, 'form-control', 'nickname',
            'Nickname', array('col-md-4', 'pb-2', 'form-floating'), false);

        echo FieldText::getField('last_name', $soldier->lastName, 'form-control', 'last-name',
            'Last Name', array('col-md-4', 'pb-2', 'form-floating'), false);


        echo FieldSelect::getField('class_id', strval($soldier->classID), 'form-control soldier-class', 'class',
            'Soldier Class', array('col-md-3', 'pb-2', 'form-floating'), true, SoldierClass::getClassArray());

        echo FieldSelect::getField('rank_id', strval($soldier->rankID), 'form-control', 'rank',
            'Soldier Rank', array('col-md-3', 'pb-2', 'form-floating'), true, Rank::getRanksByClass($soldier->classID));

        echo FieldSelect::getField('country', strval($soldier->country), 'form-control', 'country',
            'Country', array('col-md-3', 'pb-2', 'form-floating'), false, Definitions::getCountry(), $countryDisabled);

        echo FieldSelect::getField('gender', strval($soldier->gender), 'form-control', 'gender',
            'Gender', array('col-md-3', 'pb-2', 'form-floating'), true, Definitions::arrayGender());


        echo FieldTextNum::getField('aim', strval($soldier->aim), 'form-control', 'aim',
            'Aim', array('col-md-3', 'pb-2', 'form-floating'), true, false, '40', '200');

        echo FieldTextNum::getField('health', strval($soldier->health), 'form-control', 'health',
            'Health', array('col-md-3', 'pb-2', 'form-floating'), true, false, '2', '25');

        echo FieldTextNum::getField('movement', strval($soldier->movement), 'form-control', 'movement',
            'Movement', array('col-md-3', 'pb-2', 'form-floating'), true, false, '5', '30');

        echo FieldTextNum::getField('hack', strval($soldier->hack), 'form-control', 'hack',
            'Hack', array('col-md-3', 'pb-2', 'form-floating'), true, false, '0', '250');


        echo FieldTextNum::getField('dodge', strval($soldier->dodge), 'form-control', 'dodge',
            'Dodge', array('col-md-3', 'pb-2', 'form-floating'), true, false, '0', '200');

        echo FieldTextNum::getField('will', strval($soldier->will), 'form-control', 'will',
            'Will', array('col-md-3', 'pb-2', 'form-floating'), false, $willDisabled,'30', '200');

        echo FieldTextNum::getField('psi', strval($soldier->psi ?? 0), 'form-control', 'psi',
            'Psi', array('col-md-3', 'pb-2', 'form-floating'), true, $psiDisabled,'0', '200');

        echo FieldSelect::getField('intelligence', strval($soldier->intelligence), 'form-control', 'intelligence',
            'Combat Intelligence', array('col-md-3', 'pb-2', 'form-floating'), false, Definitions::getIntelligence());


        echo FieldSelect::getField('killed', strval($soldier->killed), 'form-control', 'killed',
            'Solder Status', array('col-md-3', 'pb-2', 'form-floating'), false, Definitions::getSoldierStatus());

        echo FieldTextNumDate::getField('date_joined', $soldier->joinDate, 'form-control', 'date-joined',
            'Date Joined', array('col-md-3', 'pb-2', 'form-floating'), true, false,'2035-02-28', '2038-12-31');

        echo FieldFile::getField('picture', '', 'soldier-picture', 'form-control', false, '.png, .jpg, .jpeg, .webp',
            'Image Upload', array('col-md-4','mb-3'));

        echo FieldCheckbox::getField('delete', '', 'checkbox', array(array('value' => '1')),
            'Delete Current?', array('col-md-2','mb-3'));

        if(!empty($soldier->photo)) {
            echo FieldHidden::getField('picture_current', strval($soldier->photo));
            echo '<div class="col-md-3"><img src="https://xcom-databank.games/' . $soldier->photo . '" alt="Soldier Photo" /></div>';
        }
    }

    public static function getSoldierSkillsForm(Soldier $soldier) {
        $query = "SELECT skill.id as id, skill.name as skill, cs.rank_level, ss.id as ssid, ss.soldier_id as soldierID
                    FROM xcom_skills as skill
                        INNER JOIN xcom_class_skill as cs ON skill.id = cs.skill_id
                        INNER JOIN xcom_soldier as soldier ON soldier.class_id = cs.class_id
                        LEFT JOIN xcom_soldier_skills as ss ON skill.id = ss.skills_id and ss.soldier_id = soldier.id
                    WHERE soldier.id = :soldier and skill.enabled = true and cs.enabled = 1
                    ORDER BY cs.rank_level, skill.name";
        $params[0] = array("param" => ":soldier", "var" => $soldier->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        $rankLevel = -1;
        $skillString = "";
        echo '<div class="skill-list">';
        while ($row = $queryResult->fetch()) {
            if($row['rank_level'] != $rankLevel) {
                if($rankLevel != -1) {
                    $skillString .= '</div>';
                }
                $rankLevel = $row['rank_level'];
                $skillString .= '<div class="col rank-'.$rankLevel.'">';
            }
            $shortSkill = str_replace(' ', '', $row['skill']);
            $shortSkill = str_replace("'", '', $shortSkill);
            $shortSkill = str_replace("!", '', $shortSkill);
            $skillString .= '<span class="'.$shortSkill.'">';
            if($row['soldierID'] != $soldier->id) {
                $skillString .= '<input type="checkbox" name="skills[]" value="'.$row['id'].'" id="'.$row['skill'].'">';
            } else {
                $skillString .= '<input name="soldierSkill_id[]" value="'.$row['ssid'].'" type="hidden">';
                $skillString .= '<input type="checkbox" checked name="skills[]" value="'.$row['id'].'" id="'.$row['skill'].'">';
            }
            $skillString .= '<label for ="'.$row['skill'].'" id="'.$row['id'].'">'.$row['skill'].'</label>';
            $skillString .= '</span>';
        }
        $skillString .= '</div>';
        $skillString .= '</div>';
        echo $skillString;
    }

    public static function getListPage(): void {
        $query = 'SELECT soldier.first_name, soldier.last_name, soldier.nickname, class.name as class, soldier.id as id, soldier.killed as killed, srank.name as name 
            FROM xcom_soldier as soldier 
				INNER JOIN xcom_class as class ON soldier.class_id = class.id
				INNER JOIN xcom_rank as srank ON soldier.rank_id = srank.id
			ORDER BY CASE killed
				WHEN 0 THEN 1
				WHEN 2 THEN 2
				WHEN 1 THEN 3
				ELSE 4 END, srank.level DESC, soldier.last_name';
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table mission-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-3">Rank</div>'."\n";
        $listString .= '<div class="col-6">Name</div>'."\n";
        $listString .= '<div class="col-3">Class</div>'."\n";
        $listString .= '</div>'."\n";


        while ($row = $queryResult->fetch()) {
            $rowClass = '';
            if ($row['killed'] == 0) {
                $rowClass = "row-green";
            } elseif ($row['killed'] == 2) {
                $rowClass = "row-yellow";
            } elseif ($row['killed'] == 1) {
                $rowClass = "row-red";
            }

            $listString .= '<div class="row '.$rowClass.'">'."\n";
            $listString .= '<div class="col-3">';
            $listString .= '<strong>'.$row['name'].'</strong>'."\n";
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-6">';
            $listString .= '<a href="/soldier/soldier.php?id='.$row['id'].'">'.$row['first_name'].' "'.$row['nickname'].'" '.$row['last_name'].'</a>'."\n";
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $row['class']."\n";
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    public static function getAvailableSoldierArray($header = false): array {
        $query = "SELECT * FROM xcom_soldier WHERE killed = 0 ORDER BY last_name, first_name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        if($header) {
            $typeArray = array(
                array(
                    'text' => "N/A",
                    'value' => '',
                ),
            );
        } else {
            $typeArray = array();
        }

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['last_name'].' '.$item['first_name'].' ('.$item['nickname'].')',
                'value' => $item['id'],
            );
            array_push($typeArray, $newArray);
        }

        return $typeArray;
    }

    public static function getSoldierArray($header = false, $getActiveOnly = false): array {
		if($getActiveOnly) {
			$query = "SELECT * FROM xcom_soldier WHERE killed = 0 ORDER BY last_name, first_name";
		} else {
			$query = "SELECT * FROM xcom_soldier ORDER BY last_name, first_name";
		}
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        if($header) {
            $typeArray = array(
                array(
                    'text' => "N/A",
                    'value' => '',
                ),
            );
        } else {
            $typeArray = array();
        }

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['last_name'].' '.$item['first_name'].' ('.$item['nickname'].')',
                'value' => $item['id'],
            );
            array_push($typeArray, $newArray);
        }

        return $typeArray;
    }
	
	public function soldierClass():array  {
        $query = "SELECT class.name, class.icon
			FROM xcom_class as class 
				INNER JOIN xcom_soldier as soldier ON class.id = soldier.class_id
			WHERE soldier.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

		$row = $queryResult->fetch();
		$classInfo['name'] = $row['name'];
		$classInfo['icon'] = $row['icon'];
		
		return $classInfo;
	}
	
	public function soldierRank(): array {
        $query = "SELECT srank.name, srank.level, srank.icon, srank.short
			FROM xcom_rank as srank 
				INNER JOIN xcom_soldier as soldier ON srank.id = soldier.rank_id
			WHERE soldier.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

		$row = $queryResult->fetch();
		$rankInfo['name'] = $row['name'];
		$rankInfo['icon'] = $row['icon'];
		$rankInfo['level'] = $row['level'];
		$rankInfo['short'] = $row['short'];
		
		return $rankInfo;
	}
	
	public function soldierSkills(): array {
        $query = "SELECT skill.name, skill.icon
			FROM xcom_skills as skill
				INNER JOIN xcom_soldier_skills as ss ON skill.id = ss.skills_id
				INNER JOIN xcom_soldier as soldier ON soldier.id = ss.soldier_id
			WHERE soldier.id = :id
			ORDER BY skill.name";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
		
		$skillInfo = [];
		
		while ($row = $queryResult->fetch()) {
			$skillInfo[$row['name']] = $row['icon'];
		}
		
		return $skillInfo;
	}
	
	public function soldierStats(): array {
        $query = "SELECT SUM(sm.shots_taken) as shotsTaken, SUM(sm.shots_hit) as shotsHit, SUM(sm.overwatch_taken) as overwatchTaken, SUM(sm.overwatch_hit) as overwatchHit, SUM(sm.other_taken) as otherTaken, SUM(sm.other_hit) as otherHit, SUM(sm.damage) as damage, SUM(sm.killed_aliens) as aliens, SUM(sm.killed_lost) as lost, SUM(sm.eas) as eas, date_joined as dateJoined
			FROM xcom_mission_soldier as sm
				INNER JOIN xcom_soldier as soldier ON soldier.id = sm.soldier_id
			WHERE soldier.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
		
		$row = $queryResult->fetch();
		
		if($row['dateJoined'] == null) {
			$query = "SELECT date_joined FROM xcom_soldier WHERE id = :id";
			$params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);
			$queryResult = Database::runQuery('select', $query, $params);
			$row = $queryResult->fetch();
			$row['dateJoined'] = $row['date_joined'];
			$row['shotsTaken'] = 0;
			$row['shotsHit'] = 0;
			$row['overwatchTaken'] = 0;
			$row['overwatchHit'] = 0;
			$row['otherTaken'] = 0;
			$row['otherHit'] = 0;
			$row['damage'] = 0;
			$row['eas'] = "0.00";
			$row['aliens'] = 0;
			$row['lost'] = 0;
		}

		//echo "Date joined: ".$statsInfo['dateJoined'];
        $statsInfo['dateJoined'] = $row['dateJoined'];
		$statsInfo['shots_taken'] = $row['shotsTaken'];
		$statsInfo['shots_hit'] = $row['shotsHit'];
		if($row['shotsTaken'] > 0) {
			$statsInfo['shots_pct'] = (round($row['shotsHit'] / $row['shotsTaken'], 2)) * 100;
		} else {
			$statsInfo['shots_pct'] = "0";
		}
		
		$statsInfo['overwatch_taken'] = $row['overwatchTaken'];
		$statsInfo['overwatch_hit'] = $row['overwatchHit'];
		if($row['overwatchTaken'] > 0) {
			$statsInfo['overwatch_pct'] = (round($row['overwatchHit'] / $row['overwatchTaken'], 2)) * 100;
		} else {
			$statsInfo['overwatch_pct'] = "0";
		}
		
		$statsInfo['other_taken'] = $row['otherTaken'];
		$statsInfo['other_hit'] = $row['otherHit'];
		if($row['otherTaken'] > 0) {
			$statsInfo['other_pct'] = (round($row['otherHit'] / $row['otherTaken'], 2)) * 100;
		} else {
			$statsInfo['other_pct'] = "0";
		}
		
		$statsInfo['damage'] = $row['damage'];
		$statsInfo['eas'] = (float) $row['eas'];
		
		$statsInfo['aliens_killed'] = $row['aliens'];
		$statsInfo['lost_killed'] = $row['lost'];
		$statsInfo['total_killed'] = $row['aliens'] + $row['lost'];
		
		return $statsInfo;
	}

    public static function missionSoldierList(int $id, string $type): void {
        $query = "";
        if($type == "squad") {
            $query = 'SELECT sm.id
                        FROM xcom_mission_soldier as sm
                            LEFT JOIN xcom_rank as xrank on xrank.id = sm.rank_id
                        WHERE sm.mission_id = :id AND (sm.soldier_id IS NOT NULL OR sm.extra_info = "Commander\'s Avatar")
                        ORDER BY sm.mvp desc, xrank.level desc, sm.extra';
        } else {
            $query = 'SELECT sm.id
                        FROM xcom_mission_soldier as sm
                            LEFT JOIN xcom_rank as xrank on xrank.id = sm.rank_id
                        WHERE sm.mission_id = :id AND (sm.soldier_id IS NULL AND sm.extra_info != "Commander\'s Avatar")
                        ORDER BY sm.mvp desc, xrank.level desc, sm.extra';
        }

        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_STR,);
        $queryResult = Database::runQuery('select', $query, $params);

        if($queryResult->rowCount() == 0) {
            return;
        }

        $colCount = 3;

        if($type == "squad") {?>
        <div class="card page-head mission-soldier-list-head">
            <div class="card-header bg-success text-white">Squad Information</div>
        </div>

        <?php } else { ?>

        <div class="card page-head mission-soldier-list-head">
            <div class="card-header bg-success text-white">Other Soldier Information</div>
        </div>

        <?php } ?>

        <div class="row equal-height">
        <?php
        $overall['shots_taken'] = 0;
        $overall['shots_hit'] = 0;
        $overall['overwatch_taken'] = 0;
        $overall['overwatch_hit'] = 0;
        $overall['other_taken'] = 0;
        $overall['other_hit'] = 0;
        $overall['damage'] = 0;
        $overall['eas'] = 0.00;
        $overall['aliens_killed'] = 0;
        $overall['lost_killed'] = 0;

        while ($row = $queryResult->fetch()) {

            $soldierMission = new MissionSoldier;
            $soldierMission->getMissionSoldier(intval($row['id']));

            $soldierInfo = [];
            $classInfo = [];
            $rankInfo = [];
            $soldierExtra = StatusExtra::getExtraStringByID($soldierMission->extra);
            if($soldierMission->soldierID === null) {
                $soldierInfo['name'] = $soldierMission->extraInfo;
                $soldierInfo['country'] = "resistance";

                if(in_array($soldierMission->extraInfo, array("Lily Shen","John Bradford","Celatid Turret","Commander's Avatar","XCOM Turrets"), true)) {
                    $classInfo['icon'] = "/img/icons/classes/xcom.png";
                    $soldierInfo['country'] = "xcom";
                    if($soldierMission->extraInfo == "Lily Shen") 			{ $classInfo['name'] = "Chief Engineer";		}
                    if($soldierMission->extraInfo == "John Bradford") 		{ $classInfo['name'] = "Central Officer";	}
                    if($soldierMission->extraInfo == "Celatid Turret") 		{ $classInfo['name'] = "Celatid Turret";		}
                    if($soldierMission->extraInfo == "Commander's Avatar")	{ $classInfo['name'] = "Avatar";				}
                    if($soldierMission->extraInfo == "XCOM Turrets")	{ $classInfo['name'] = "XCOM Turrets";				}
                }
                elseif($soldierExtra == "Mind Controlled") {
                    $soldierInfo['country'] = "advent";
                    $classInfo['icon'] = "/img/icons/mind-control.png";
                    $classInfo['name'] = "Mind Controlled Enemy";
                }
                elseif($soldierExtra == "Dominated") {
                    $soldierInfo['country'] = "advent";
                    $classInfo['icon'] = "/img/icons/mind-control.png";
                    $classInfo['name'] = "Dominated Enemy";
                }
                elseif($soldierExtra == "Double Agent") {
                    $soldierInfo['country'] = "advent";
                    $classInfo['icon'] = "/img/icons/classes/advent.png";
                    $classInfo['name'] = "Double Agent";
                }
                elseif($soldierExtra == "Hacked") {
                    $soldierInfo['country'] = "advent";
                    $classInfo['icon'] = "/img/icons/classes/alien.png";
                    $classInfo['name'] = "Hacked ADVENT Machinery";
                }
                elseif($soldierExtra == "Granted Resistance") {
                    $classInfo['icon'] = "/img/icons/classes/resistance.png";
                    $classInfo['name'] = "Resistance Support";
                }
                elseif($soldierExtra == "Volunteer Army") {
                    $classInfo['icon'] = "/img/icons/classes/resistance.png";
                    $classInfo['name'] = "Volunteer Army";
                }
                elseif(in_array($soldierExtra, array("Resistance Soldiers","Resistance Operative","Resistance MEC", "Resistance Android", "Resistance Militia", "Militia Skirmishers", "Militia MECs", "Militia Androids"), true)) {
                    $classInfo['icon'] = "/img/icons/classes/resistance.png";
                    $classInfo['name'] = StatusExtra::getExtraStringByID($soldierMission->extra);
                }
                elseif($soldierExtra == "Spawned Item/Ability") {
                    $soldierInfo['country'] = "xcom";
                    $classInfo['icon'] = "/img/icons/classes/xcom.png";
                    $classInfo['name'] = $soldierInfo['name'];
                }
                elseif($soldierExtra == "Skirmisher Warrior") {
                    $soldierInfo['country'] = "skirmishers";
                    $classInfo['icon'] = "/img/class/skirmishers.png";
                    $classInfo['name'] = $soldierExtra;
                }
                elseif($soldierExtra == "Reaper Agent") {
                    $soldierInfo['country'] = "reapers";
                    $classInfo['icon'] = "/img/class/reaper.png";
                    $classInfo['name'] = $soldierExtra;
                }
                elseif($soldierExtra == "Reanimated Zombie") {
                    $soldierInfo['country'] = "xcom";
                    $classInfo['icon'] = "/img/icons/classes/xcom.png";
                    $classInfo['name'] = "Reanimated Zombie";
                }
            } else {
                $classInfo = $soldierMission->smClassInfo();
                $rankInfo = $soldierMission->smRankInfo();
                $soldierInfo = $soldierMission->smSoldierInfo();

                if($classInfo['name'] == "Reaper")			{	$soldierInfo['country'] = "reapers";		}
                elseif($classInfo['name'] == "Skirmisher")	{	$soldierInfo['country'] = "skirmishers";	}
                elseif($classInfo['name'] == "Templar")		{	$soldierInfo['country'] = "templars";		}
                elseif(strstr($classInfo['name'],"Spark"))		{	$soldierInfo['country'] = "spark";			}
                else {
                    $soldierInfo['country'] = str_replace(" ","-",strtolower($soldierInfo['country']));

                    // Check for Black ADVENT flag
                    if($soldierInfo['country'] == "advent") {
                        $soldierInfo['country'] = "advent-dark";
                    }
                }
            }

            $statsInfo = $soldierMission->smStatsInfo();

            if($statsInfo['aliens_killed'] > 0 and $statsInfo['lost_killed'] == 0) {
                $killedText = $statsInfo['aliens_killed']." Aliens";
            }
            elseif($statsInfo['aliens_killed'] == 0 and $statsInfo['lost_killed'] > 0) {
                $killedText = $statsInfo['lost_killed']." Lost";
            }
            elseif($statsInfo['aliens_killed'] > 0 and $statsInfo['lost_killed'] > 0) {
                $killedText = $statsInfo['aliens_killed']." Aliens<br />".$statsInfo['lost_killed']." Lost";
            }
            else {
                $killedText = "None";
            }

            $statusString = "";

            if($soldierMission->mvp == 1) {
                $statusString .= '<i class="fas fa-trophy fa-2x text-warning" title="MVP"></i>';
            }

            if($soldierMission->promoted == 1) {
                $statusString .= '<i class="fas fa-caret-square-up fa-2x text-success" title="Promoted"></i>';
            }

            if($soldierMission->status == "Killed") {
                $statusString .= '<i class="fas fa-skull fa-2x text-danger" title="Killed"></i>';
            }
            elseif($soldierMission->status == "Captured") {
                $statusString .= '<i class="fas fa-dungeon fa-2x text-danger" title="Captured"></i>';
            }

            if($soldierMission->status == "Lightly Wounded") {
                $statusString .= '<i class="fas fa-user-injured fa-2x text-warning" title="Lightly Wounded"></i>';
            }
            elseif($soldierMission->status == "Wounded") {
                $statusString .= '<i class="fas fa-user-injured fa-2x text-danger" title="Wounded"></i>';
            }
            elseif($soldierMission->status == "Gravely Wounded") {
                $statusString .= '<i class="fas fa-procedures fa-2x text-danger" title="Gravely Wounded"></i>';
            }
            elseif($soldierMission->status == "Shaken") {
                $statusString .= '<i class="fas fa-heart-broken fa-2x text-danger" title="Shaken"></i>';
            }

            if($soldierExtra == "Hacked") {
                $statusString .= '<i class="fas fa-code fa-2x text-info" title="Hacked"></i>';
            }
            elseif($soldierExtra == "Mind Controlled") {
                $statusString .= '<i class="fas fa-head-side-virus fa-2x text-psi" title="Mind Controlled"></i>';
            }
            elseif($soldierExtra == "Dominated") {
                $statusString .= '<i class="fas fa-head-side-virus fa-2x text-psi" title="Dominated"></i>';
            }
            elseif($soldierExtra == "Reanimated Zombie") {
                $statusString .= '<i class="fas fa-biohazard fa-2x text-psi" title="Reanimated Zombie"></i>';
            }
            elseif($soldierExtra == "Double Agent") {
                $statusString .= '<i class="fas fa-user-secret fa-2x text-info" title="Double Agent"></i>';
            }
            elseif($soldierExtra == "Granted Resistance" or $soldierExtra == "Volunteer Army") {
                $statusString .= '<i class="fas fa-user-shield fa-2x text-info" title="Volunteer Army"></i>';
            }
            elseif($soldierExtra == "Resistance Militia") {
                $statusString .= '<i class="fas fa-user-shield fa-2x text-info" title="Resistance Militia"></i>';
            }
            elseif($soldierExtra == "Militia Skirmishers") {
                $statusString .= '<i class="fas fa-user-shield fa-2x text-info" title="Militia Skirmishers"></i>';
            }
            elseif($soldierExtra == "Militia MECs") {
                $statusString .= '<i class="fas fa-robot fa-2x text-info" title="Militia MECs"></i>';
            }
            elseif($soldierExtra == "Militia Androids") {
                $statusString .= '<i class="fas fa-robot fa-2x text-info" title="Militia Androids"></i>';
            }
            elseif($soldierExtra == "Rescued Soldier") {
                $statusString .= '<i class="fas fa-life-ring fa-2x text-success" title="Rescued"></i>';
            }

            $overall['shots_taken'] 	+= $statsInfo['shots_taken'];
            $overall['shots_hit'] 		+= $statsInfo['shots_hit'];
            $overall['overwatch_taken'] += $statsInfo['overwatch_taken'];
            $overall['overwatch_hit'] 	+= $statsInfo['overwatch_hit'];
            $overall['other_taken']		+= $statsInfo['other_taken'];
            $overall['other_hit']		+= $statsInfo['other_hit'];
            $overall['damage']			+= $statsInfo['damage'];
            $overall['aliens_killed']	+= $statsInfo['aliens_killed'];
            $overall['lost_killed']		+= $statsInfo['lost_killed'];

            if(is_numeric($statsInfo['eas'])) {
                $overall['eas']			+= (float)$statsInfo['eas'];
            }

            $countryName = ucwords(str_replace("-"," ",$soldierInfo['country']));

            $soldierImg = "";
            $solderImgAlt = "";
            if($soldierMission->soldierID == null) {
                $displayName = $soldierInfo['name'];
                $rankInfo['icon'] = "";
                if($soldierExtra == "Hacked") {
                    $soldierImg = "/img/extra/hacked.jpg";
                    $solderImgAlt = "Hacked Alien Robot";
                }
                elseif($soldierExtra == "Mind Controlled" or $soldierExtra == "Dominated") {
                    $soldierImg = "/img/extra/mind-controlled.jpg";
                    $solderImgAlt = "Mind Controlled Alien";
                }
                elseif($soldierExtra == "Reanimated Zombie") {
                    $soldierImg = "/img/extra/zombie.jpg";
                    $solderImgAlt = "Reanimated Zombie";
                }
                elseif($soldierExtra == "Double Agent") {
                    $soldierImg = "/img/extra/double-agent.jpg";
                    $solderImgAlt = "ADVENT Double Agent";
                }
                elseif($soldierExtra == "Volunteer Army" or $soldierExtra == "Granted Resistance" or $soldierExtra == "Resistance Militia" or $soldierExtra == "Resistance Operative") {
                    $soldierImg = "/img/extra/militia.jpg";
                    $solderImgAlt = "Resistance Militia";
                }
                elseif($soldierExtra == "Militia Skirmishers") {
                    $soldierImg = "/img/extra/skirmisher.jpg";
                    $solderImgAlt = "Militia Skirmisher";
                }
                elseif($soldierExtra == "Militia MECs") {
                    $soldierImg = "/img/extra/mec.jpg";
                    $solderImgAlt = "Militia MEC";
                }
                elseif($soldierExtra == "Militia Androids") {
                    $soldierImg = "/img/extra/android.jpg";
                    $solderImgAlt = "Militia Android";
                }
                elseif($soldierExtra == "Commander's Avatar") {
                    $soldierImg = "/img/extra/commander.jpg";
                    $solderImgAlt = "Commander's Avatar";
                }
                elseif($soldierExtra == "Skirmisher Warrior") {
                    $soldierImg = "/img/extra/skirmisher-warrior.jpg";
                    $solderImgAlt = "Skirmisher Warrior";
                }
                elseif($soldierExtra == "Reaper Agent") {
                    $soldierImg = "/img/extra/reaper-agent.jpg";
                    $solderImgAlt = "Reaper Agent";
                }
            } elseif($soldierInfo['nickname'] == "") {
                $displayName = '<a href="/soldier/'.str_replace(" ", "-", strtolower($soldierInfo['first_name'])).'~'.str_replace(" ","-",strtolower($soldierInfo['last_name'])).'/">'.$soldierInfo['first_name'].' '.$soldierInfo['last_name']."</a>";
                $soldierImg = $soldierInfo['photo'];
                $solderImgAlt = $soldierInfo['first_name'].' '.$soldierInfo['last_name'];
            }
            else {
                $displayName = '<a href="/soldier/'.str_replace(" ","-",strtolower($soldierInfo['first_name'])).'~'.str_replace(" ","-",strtolower($soldierInfo['last_name'])).'/">'.$soldierInfo['first_name'].' "'.$soldierInfo['nickname'].'" '.$soldierInfo['last_name']."</a>";
                $soldierImg = $soldierInfo['photo'];
                $solderImgAlt = $soldierInfo['first_name']." '".$soldierInfo['nickname']."' ".$soldierInfo['last_name'];
            }

            if($soldierMission->mvp == 1) {
                $borderClass = "border-warning";
            } elseif($soldierMission->status == "Killed") {
                $borderClass = "border-danger";
            } elseif($soldierMission->promoted == 1) {
                $borderClass = "border-success";
            } else {
                $borderClass = "border-secondary";
            }

        ?>
            <div class="col-12 col-md-6 col-lg-6 mb-3">
                <div class="card soldier <?php echo $borderClass; ?>">
                    <div class="row g-0">
                        <div class="col-xl-4 d-none d-md-flex mission-soldier-picture">
                            <img src="<?php echo $soldierImg; ?>" class="img-fluid rounded-start" alt="<?php echo $solderImgAlt; ?>">
                        </div>
                        <div class="col-12 col-xl-8">
                            <div class="card-header row soldier-mission-head">
                                <div class="col-8 soldier-mission-name"><?php echo $displayName; ?></div>
                                <div class="col-4 soldier-mission-status"><? echo $statusString; ?></div>
                            </div>
                            <div class="card-body">
                                <div class="row soldier-info">
                                    <div class="col-12 col-md-3 flag"><img src="/img/flags/<?php echo $soldierInfo['country']; ?>.png" alt="<?php echo $countryName; ?>" title="<?php echo $countryName; ?>"></div>
                                    <?php
                                    if($rankInfo['icon'] == "") {
                                        ?>
                                        <div class="col-12 col-md-9 class">
                                            <div class="row">
                                                <div class="col-12 col-md-2 icon"><img src="<?php echo $classInfo['icon']; ?>" alt="<?php echo $classInfo['name']; ?>" title="<?php echo $classInfo['name']; ?>"></div>
                                                <div class="d-none d-md-flex col-md-10 soldier-info-text"><?php echo $classInfo['name']; ?></div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    else
                                    {
                                        ?>
                                        <div class="col-6 col-md-4 rank">
                                            <div class="row">
                                                <div class="col-12 col-md-4 icon"><img src="<?php echo $rankInfo['icon']; ?>" alt="<?php echo $rankInfo['name']; ?>" title="<?php echo $rankInfo['name']; ?>"></div>
                                                <div class="d-none d-md-flex col-md-8 soldier-info-text"><span class="align-middle"><?php echo $rankInfo['name']; ?></span></div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-5 class">
                                            <div class="row">
                                                <div class="col-12 col-md-3 icon"><img src="<?php echo $classInfo['icon']; ?>" alt="<?php echo $classInfo['name']; ?>" title="<?php echo $classInfo['name']; ?>"></div>
                                                <div class="d-none d-md-flex col-md-9 soldier-info-text"><?php echo $classInfo['name']; ?></div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-6 col-md-4">
                                        <div class="col"><strong>Shots</strong></div>
                                        <div class="col"><?php echo $statsInfo['shots_hit'].'/'.$statsInfo['shots_taken']; ?></div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="col"><strong>OW</strong></div>
                                        <div class="col"><?php echo $statsInfo['overwatch_hit'].'/'.$statsInfo['overwatch_taken']; ?></div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="col"><strong>Other</strong></div>
                                        <div class="col"><?php echo $statsInfo['other_hit'].'/'.$statsInfo['other_taken']; ?></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6 col-md-4">
                                        <div class="col"><strong>Dmg.</strong></div>
                                        <div class="col"><?php echo $statsInfo['damage']; ?></div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="col"><strong>Kills</strong></div>
                                        <div class="col"><?php echo $killedText; ?></div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="col"><strong>EAS</strong></div>
                                        <div class="col"><?php echo number_format($statsInfo['eas'],2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
        </div>
        <?php

        $overall['shots_pct'] = "N/A";
        $overall['overwatch_pct'] = "N/A";
        $overall['other_pct'] = "N/A";

        if($overall['shots_taken'] > 0) {
            $overall['shots_pct'] = (round($overall['shots_hit'] / $overall['shots_taken'], 2)) * 100;
        }
        if($overall['overwatch_taken'] > 0) {
            $overall['overwatch_pct'] = (round($overall['overwatch_hit'] / $overall['overwatch_taken'], 2)) * 100;
        }
        if($overall['other_taken'] > 0) {
            $overall['other_pct'] = (round($overall['other_hit'] / $overall['other_taken'], 2)) * 100;
        }
        if($overall['aliens_killed'] > 0 and $overall['lost_killed'] == 0) {
            $overall['killed_text'] = $overall['aliens_killed']." (A)";
        }
        elseif($overall['aliens_killed'] == 0 and $overall['lost_killed'] > 0) {
            $overall['killed_text'] = $overall['lost_killed']." (L)";
        }
        elseif($overall['aliens_killed'] > 0 and $overall['lost_killed'] > 0) {
            $overall['killed_text'] = $overall['aliens_killed']." (A), ".$overall['lost_killed']." (L)";
        }
        else {
            $overall['killed_text'] = "0";
        }
        ?>

        <div class="card-deck mb-3">
            <div class="card summation">
                <div class="card-header"><strong>Summary</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm"><strong>Shots:</strong> <?php echo $overall['shots_hit'].'/'.$overall['shots_taken'].'<span class="shot-pct"> ('.$overall['shots_pct'].'%)</span>'; ?></div>
                        <div class="col-sm"><strong>Overwatch:</strong> <?php echo $overall['overwatch_hit'].'/'.$overall['overwatch_taken'].'<span class="shot-pct"> ('.$overall['overwatch_pct'].'%)</span>'; ?></div>
                        <div class="col-sm"><strong>Damage:</strong> <?php echo $overall['damage']; ?></div>
                        <div class="col-sm"><strong>Kills:</strong> <?php echo $overall['killed_text']; ?></div>
                        <div class="col-sm"><strong>EAS:</strong> <?php echo number_format($overall['eas'],2); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}