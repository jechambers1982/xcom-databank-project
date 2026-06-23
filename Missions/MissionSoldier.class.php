<?php
declare(strict_types = 1);

namespace XCOMDatabank\Missions;

use PDO;
use XCOMDatabank\Forms\Field;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldText;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Management\SoldierClass;
use XCOMDatabank\Management\Rank;
use XCOMDatabank\Soldiers\Soldier;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Definitions;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;
class MissionSoldier
{
    public ?int $id;
	public ?int $soldierID; 	// database id from xcom_soldier
	public int $missionID; 		// database id from xcom_mission
	public ?int $rankID;		// database id from xcom_rank
	public ?int $classID;		// database id from xcom_class
	public int $shotsTaken; 	// shots taken, normal and overwatch
	public int $shotsHit; 		// shots hit, normal and overwatch
	public int $overwatchTaken;	// shots taken, overwatch only
	public int $overwatchHit;	// shots hit, overwatch only
	public int $otherTaken;		// melee shots Taken
	public int $otherHit;		// melee shots Hit
	public int $damage;			// damage dealt
    public ?float $eas;        // eas score
    public int $healing;        // healing performed
	public int $killedAliens;	// Aliens killed (not including Lost)
	public int $killedLost;		// Lost killed
	public bool $mvp;			// Did soldier get mission MPV?
	public ?string $status;		// Injury report. E.g. "lightly wounded," "gravely wounded," "killed," "captured," "active"
	public ?int $extra;			// database id of "extra" statuses
	public ?string $extraInfo;	// Additional info if $status is not empty.
	public bool $promoted;		// Was the soldier promoted?
		
	function __construct() {
        $this->id = null;
		$this->soldierID = null;
		$this->missionID = 0;
		$this->rankID = null;
		$this->classID = null;
		$this->shotsTaken = 0;
		$this->shotsHit = 0;
		$this->overwatchTaken = 0;
		$this->overwatchHit = 0;
		$this->otherTaken = 0;
		$this->otherHit = 0;
		$this->damage = 0;
        $this->eas = null;
        $this->healing = 0;
		$this->killedAliens = 0;
		$this->killedLost = 0;
		$this->mvp = false;
		$this->status = null;
		$this->extra = null;
		$this->extraInfo = null;
		$this->promoted = false;
	}
		
	public function newMissionSoldier(array $missionSoldier)
	{
        if($missionSoldier['soldier_id'] == "") {
            $missionSoldier['soldier_id'] = null;
        }
        if($missionSoldier['rank_id'] ==  "") {
            $missionSoldier['rank_id'] =  null;
        }

        $this->validateMissionSoldier($missionSoldier);

        $query = "INSERT INTO xcom_mission_soldier VALUES (NULL, :soldierID, :missionID, :rankID, :classID, :shotsTaken, 
                    :shotsHit, :overwatchTaken, :overwatchHit, :otherTaken, :otherHit, :damage, :eas, :healing, :killedAliens, :killedLost, 
                    :mvp, :status, :extra, :extraInfo, :promoted)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getMissionSoldier(int $id)
	{
        $query = "SELECT * FROM xcom_mission_soldier WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->soldierID = intval($row['soldier_id']) ?: null;
		$this->missionID = intval($row['mission_id']);
		$this->rankID = intval($row['rank_id']) ?: null;
		$this->classID = intval($row['class_id']) ?: null;
		$this->shotsTaken = intval($row['shots_taken']);
		$this->shotsHit = intval($row['shots_hit']);
		$this->overwatchTaken = intval($row['overwatch_taken']);
		$this->overwatchHit = intval($row['overwatch_hit']);
		$this->otherTaken = intval($row['other_taken']);
		$this->otherHit = intval($row['other_hit']);
		$this->damage = intval($row['damage']);
        $this->eas = floatval($row['eas']);
        $this->healing = intval($row['healing']);
		$this->killedAliens = intval($row['killed_aliens']);
		$this->killedLost = intval($row['killed_lost']);
		$this->mvp = boolval($row['mvp']);
		$this->status = $row['status'];
		$this->extra = intval($row['extra']) ?: null;
		$this->extraInfo = $row['extra_info'];
		$this->promoted = boolval($row['promoted']);
	}
		
	public function editMissionSoldier(array $missionSoldier)
	{
        if($missionSoldier['soldier_id'] == "") {
            $missionSoldier['soldier_id'] = null;
        }
        if($missionSoldier['rank_id'] ==  "") {
            $missionSoldier['rank_id'] =  null;
        }

        $this->validateMissionSoldier($missionSoldier);

        $query = "UPDATE xcom_mission_soldier SET soldier_id = :soldierID, mission_id = :missionID, rank_id = :rankID, class_id = :classID, 
                    shots_taken = :shotsTaken, shots_hit = :shotsHit, overwatch_taken = :overwatchTaken, overwatch_hit = :overwatchHit, 
                    other_taken = :otherTaken, other_hit = :otherHit, damage = :damage, eas = :eas, healing = :healing, killed_aliens = :killedAliens, 
                    killed_lost = :killedLost, mvp = :mvp, status = :status, extra = :extra, extra_info = :extraInfo, promoted = :promoted 
                    WHERE id = :id";
        $params = $this->getParams();
        $params[20] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT);

        Database::runQuery('update', $query, $params);
	}

    private function validateMissionSoldier(array $submit): void {
        // If a Mission Soldier ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex((int)$submit['id'], 'mission_soldier', false, "Mission Soldier ID");
        }

        // Make sure Soldier ID is valid
        $this->soldierID = Validate::testIndex((int)$submit['soldier_id'] ?? null, 'soldier', true, "Soldier ID");

        // Make sure Mission ID is valid
        $this->missionID = Validate::testIndex((int)$submit['mission_id'], 'mission', false, "Mission ID");

        // Make sure Rank ID is valid or null
        $this->rankID = Validate::testIndex((int)$submit['rank_id'] ?? null, 'rank', true, "Rank ID");

        // Make sure Class ID is valid or null
        $this->classID = Validate::testIndex((int)$submit['class_id'] ?? null, 'class', true, "Class ID");

        // Make sure shots Taken and shots Hit are Integers
        $this->shotsTaken = Validate::testInteger((int)$submit['shots_taken'], 0, 100, false, "Shots Taken");
        $this->shotsHit = Validate::testInteger((int)$submit['shots_hit'], 0, $this->shotsTaken, false, "Shots Hit");

        // Make sure overwatch Taken and overwatch Hit are Integers
        $this->overwatchTaken = Validate::testInteger((int)$submit['overwatch_taken'], 0, 100, false, "Overwatch Taken");
        $this->overwatchHit = Validate::testInteger((int)$submit['overwatch_hit'], 0, $this->overwatchTaken, false, "Overwatch Hit");

        // Make sure other attacks Taken and other attacks Hit are Integers
        $this->otherTaken = Validate::testInteger((int)$submit['other_taken'], 0, 100, false, "Other Attacks Taken");
        $this->otherHit = Validate::testInteger((int)$submit['other_hit'], 0, $this->otherTaken, false, "Other Attacks Hit");

        // Make sure Damage done is an Integer
        $this->damage = Validate::testInteger((int)$submit['damage'], 0, 500, false, "Damage");

        // Make sure EAS a Floast value or null
        $this->eas = Validate::testFloat((float)$submit['eas'] ?? null, -100, 100, true, "EAS");

        // Make sure Healing done is an Integer
        $this->healing = Validate::testInteger((int)$submit['healing'], 0, 100, false, "Healing");

        // Make sure aliens killed and lost killed are Integers
        $this->killedAliens = Validate::testInteger((int)$submit['killed_aliens'], 0, 100, false, "Aliens Killed");
        $this->killedLost = Validate::testInteger((int)$submit['killed_lost'], 0, 100, false, "Lost Killed");

        // Test if Soldier is MVP or not
        $this->mvp = Validate::testTF((bool)$submit['mvp'] ?? false, false, "Mission MVP");

        // Make sure Soldier Status is valid
        $this->status = Validate::testArray($submit['status'] ?? null, Definitions::getStatus(), true, "Soldier Mission Status");

        // Make sure Extra ID is valid or null
        $this->extra = Validate::testIndex((int)$submit['extra'] ?? null, 'status_extra', true, "Extra ID");

        // Test if Extra Info id String or null
        $this->extraInfo = Validate::testString($submit['extra_info'] ?? null, -1, -1, true, "Extra Info");

        // Make sure Promoted is a boolean value
        $this->promoted = Validate::testTF((bool)$submit['promoted'] ?? false, false, "Promotion");
    }

    public function processForm(array $submit, string $redirect = ''): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editMissionSoldier($submit);
            } else {
                return Error::returnError("Mission Soldier ID is set, but Mission Soldier ID is not numeric");
            }
        } else {
            $this->newMissionSoldier($submit);
        }
        if(!empty($redirect)) {
            header('Location: '.$redirect);
        }
        return "";
    }

    public static function getMissionSoldierForm(MissionSoldier $missionSoldier, bool $multi, bool $lastElement) {
        $nameA = "";
        if($multi) {
            $nameA = "[]";
        }

        $id = $missionSoldier->id;
        if($id != "" and $id !== null and is_numeric($id)) {
            echo FieldHidden::getField('id'.$nameA,strval($id));
        }

        echo FieldSelect::getField('soldier_id'.$nameA, strval($missionSoldier->soldierID), 'form-control soldierid', 'soldierid',
            'Soldier', array('col-md-3','pb-2','pe-3','form-floating'), false, Soldier::getSoldierArray('true', 'false'));

        echo FieldSelect::getField('rank_id'.$nameA, strval($missionSoldier->rankID), 'form-control rank', 'rank',
            'Rank', array('col-md-2','pb-2','pe-3','form-floating'), false, Rank::getRankArray());

        echo FieldSelect::getField('class_id'.$nameA, strval($missionSoldier->classID), 'form-control class', 'class',
            'Class', array('col-md-2','pb-2','pe-3','form-floating'), false, SoldierClass::getClassArray());

        echo FieldTextNum::getField('damage'.$nameA, strval($missionSoldier->damage), 'form-control', 'damage',
            'Damage', array('col-md-2', 'pb-2', 'pe-3', 'form-floating'), true, false, '0', '500');

        echo FieldSelect::getField('promoted'.$nameA, strval($missionSoldier->promoted), 'form-control', 'promoted',
            'Promoted?', array('col-md-2','pb-2','pe-3', 'form-floating'), true, Definitions::arrayYesNo());

        echo FieldSelect::getField('mvp'.$nameA, strval($missionSoldier->mvp), 'form-control', 'mvp',
            'MVP?', array('col-md-1','pb-2','pe-3', 'form-floating'), true, Definitions::arrayYesNo());

        //Have to do special stuff for Shots Hit/Taken field combination
        echo '<div class="pe-3 pb-2 mt-2 col-md-2">'."\n";
        echo '<div><strong>Shots</strong></div>'."\n";
        echo '<div class="input-group">'."\n";
        echo FieldTextNum::getField('shots_hit'.$nameA, strval($missionSoldier->shotsHit), 'form-control', 'shots-hit','', array(), true, false,'0', '100');
        echo '<span class="input-group-text">/</span>'."\n";
        echo FieldTextNum::getField('shots_taken'.$nameA, strval($missionSoldier->shotsTaken), 'form-control', 'shots-taken','', array(), true, false,'0', '100');
        echo '</div>'."\n";
        echo '</div>'."\n";

        //Have to do special stuff for Overwatch Hit/Taken field combination
        echo '<div class="pe-3 pb-2 mt-2 col-md-2">'."\n";
        echo '<div><strong>Overwatch</strong></div>'."\n";
        echo '<div class="input-group">'."\n";
        echo FieldTextNum::getField('overwatch_hit'.$nameA, strval($missionSoldier->overwatchHit), 'form-control', 'overwatch-hit','', array(), true, false,'0', '100');
        echo '<span class="input-group-text">/</span>'."\n";
        echo FieldTextNum::getField('overwatch_taken'.$nameA, strval($missionSoldier->overwatchTaken), 'form-control', 'overwatch-taken','', array(), true, false,'0', '100');
        echo '</div>'."\n";
        echo '</div>'."\n";

        //Have to do special stuff for Other Attack Hit/Taken field combination
        echo '<div class="pe-3 pb-2 mt-2 col-md-2">'."\n";
        echo '<div><strong>Other</strong></div>'."\n";
        echo '<div class="input-group">'."\n";
        echo FieldTextNum::getField('other_hit'.$nameA, strval($missionSoldier->otherHit), 'form-control', 'other-hit','', array(), true, false,'0', '100');
        echo '<span class="input-group-text">/</span>'."\n";
        echo FieldTextNum::getField('other_taken'.$nameA, strval($missionSoldier->otherTaken), 'form-control', 'other-taken','', array(), true, false,'0', '100');
        echo '</div>'."\n";
        echo '</div>'."\n";

        echo FieldTextNum::getField('healing'.$nameA, strval($missionSoldier->healing), 'form-control', 'healing',
            'Healed', array('col-md-2', 'pb-2', 'pe-3', 'form-floating'), true, false,'0', '100');

        echo FieldTextNum::getField('killed_aliens'.$nameA, strval($missionSoldier->killedAliens), 'form-control', 'killed-aliens',
            'Aliens', array('col-md-2', 'pb-2', 'pe-3', 'form-floating'), true, false,'0', '25');

        echo FieldTextNum::getField('killed_lost'.$nameA, strval($missionSoldier->killedLost), 'form-control', 'killed-lost',
            'Lost', array('col-md-2', 'pb-2', 'pe-3', 'form-floating'), true, false,'0', '100');


        echo FieldSelect::getField('status'.$nameA, $missionSoldier->status, 'form-control', 'status',
            'Status', array('col-md-2','pb-2', 'pe-3', 'form-floating'), true, Definitions::getStatus());

        echo FieldSelect::getField('extra'.$nameA, strval($missionSoldier->extra), 'form-control', 'extra',
            'Special Status', array('col-md-2','pb-2','pe-3','form-floating'), false, StatusExtra::getExtraArray());

        echo FieldText::getField('extra_info'.$nameA, $missionSoldier->extraInfo, 'form-control', 'extra-info',
            'Info', array('col-md-2', 'pb-2', 'pe-3', 'form-floating'), false);

        echo FieldTextNum::getField('eas'.$nameA, strval($missionSoldier->eas), 'form-control', 'eas',
            'EAS', array('col-md-2', 'pb-2', 'pe-3', 'form-floating'), true, false, '-99.99', '99.99','0.01');

         if($multi) {
            echo Field::repeatButton($lastElement);
         } else {
            echo FieldSelect::getField('mission_id'.$nameA, strval($missionSoldier->missionID), 'form-control', 'mission-id',
                'Mission', array('col-md-4','pb-2','pe-3','form-floating'), true, Mission::getMissionArray());
        }
    }
	
	public function smClassInfo(): array {
        $query = "SELECT name, icon FROM xcom_class WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $this->classID, "type" => PDO::PARAM_INT,);
        $queryResult = Database::runQuery('select', $query, $params);

        $row = $queryResult->fetch();

        $classInfo['name'] = $row['name'];
        $classInfo['icon'] = $row['icon'];
		
		return $classInfo;
	}

    public static function getListPage(): void {
        $query = "SELECT mission_soldier.id as id, soldier.first_name as firstName, soldier.nickname as nickname, soldier.last_name as lastName, 
                    mission_soldier.extra_info as extra, mission.operation_name as name
			FROM xcom_mission_soldier as mission_soldier 
				INNER JOIN xcom_mission as mission ON mission_soldier.mission_id = mission.id 
				LEFT JOIN xcom_soldier as soldier ON mission_soldier.soldier_id = soldier.id 
			ORDER BY mission.mission_date DESC, mission.operation_name, mission_soldier.extra_info, soldier.last_name, soldier.nickname";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);
        $missionName = "";
        ?>
        <div class="container admin-table mission-soldier-list">
            <div class="row head-row">
                <div class="col-5">Mission</div>
                <div class="col-7">Soldier</div>
            </div>
        <?php
        foreach ($queryResult as $row) {
            if($missionName != $row['name']) {
                if($missionName != "") {
        ?>
                    </div>
                </div>
            </div>
        <?php
                }
                $missionName = $row['name']; ?>
            <div class="row py-0">
                <div class="col-5"><?php echo $row['name']; ?></div>
                <div class="col-7">
                    <div class="row">
        <?php
            } ?>
                        <div class="col-12">
        <?php
            if(!empty($row['extra'])) { ?>
                            <a href="/mission/mission-soldier.php?id=<?php echo $row['id']; ?>"><?php echo $row['extra']; ?></a>
        <?php
            } else { ?>
                            <a href="/mission/mission-soldier.php?id=<?php echo $row['id']; ?>"><?php echo $row['firstName'].' "'.$row['nickname'].'" '.$row['lastName']; ?></a>
        <?php
            } ?>
                        </div>
        <?php
        } ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
	
	public function smRankInfo(): array {
        $query = "SELECT xcom_rank.name, xcom_rank.level, xcom_rank.icon, xcom_rank.short FROM xcom_rank
				INNER JOIN xcom_mission_soldier as ms ON xcom_rank.id = ms.rank_id
			    WHERE ms.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);
        $queryResult = Database::runQuery('select', $query, $params);

        $row = $queryResult->fetch();
		
		$rankInfo['name'] = $row['name'];
		$rankInfo['icon'] = $row['icon'];
		$rankInfo['level'] = $row['level'];
		$rankInfo['short'] = $row['short'];
		
		return $rankInfo;
	}
	
	public function smStatsInfo(): array {
		
		$statsInfo['shots_taken'] = $this->shotsTaken;
		$statsInfo['shots_hit'] = $this->shotsHit;
		if($this->shotsTaken > 0) {
			$statsInfo['shots_pct'] = (round($this->shotsHit / $this->shotsTaken, 2)) * 100;
		} else {
			$statsInfo['shots_pct'] = "N/A";
		}
		
		$statsInfo['overwatch_taken'] = $this->overwatchTaken;
		$statsInfo['overwatch_hit'] = $this->overwatchHit;
		if($this->overwatchTaken > 0) {
			$statsInfo['overwatch_pct'] = (round($this->overwatchHit / $this->overwatchTaken, 2)) * 100;
		} else {
			$statsInfo['overwatch_pct'] = "N/A";
		}
		
		$statsInfo['other_taken'] = $this->otherTaken;
		$statsInfo['other_hit'] = $this->otherHit;
		if($this->otherTaken > 0) {
			$statsInfo['other_pct'] = (round($this->otherHit / $this->otherTaken, 2)) * 100;
		} else {
			$statsInfo['other_pct'] = "N/A";
		}
		
		$statsInfo['damage'] = $this->damage;
        $statsInfo['eas'] = $this->eas;
        $statsInfo['healing'] = $this->healing;
		$statsInfo['mvp'] = $this->mvp;
		
		$statsInfo['aliens_killed'] = $this->killedAliens;
		$statsInfo['lost_killed'] = $this->killedLost;
		$statsInfo['total_killed'] = $this->killedAliens + $this->killedLost;
		
		return $statsInfo;
	}
	
	public function smSoldierInfo(): array {
        $query = "SELECT soldier.first_name, soldier.last_name, soldier.nickname, soldier.country, soldier.photo
			FROM xcom_soldier as soldier 
				INNER JOIN xcom_mission_soldier as sm ON soldier.id = sm.soldier_id
			WHERE sm.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);
        $queryResult = Database::runQuery('select', $query, $params);

        $row = $queryResult->fetch();

		$soldierInfo['first_name'] = $row['first_name'];
		$soldierInfo['last_name'] = $row['last_name'];
		$soldierInfo['nickname'] = $row['nickname'];
		$soldierInfo['name'] = $row['first_name'].' "'.$row['nickname'].'" '.$row['last_name'];
		$soldierInfo['country'] = $row['country'];
		$soldierInfo['photo'] = $row['photo'];
		
		return $soldierInfo;
	}
	
	public function smStatusInfo(): array {
		
		$statusInfo = [];
		
		if($this->status != "Active" and $this->status != null) {
			$status['label'] = $this->status;
		
			if($this->status == "Rescued") {
				$status['class'] = "alert-info";
			}
			elseif(in_array($this->status, array("Shaken","Wounded","Lightly Wounded","Gravely Wounded"), true)) {
				$status['class'] = "alert-warning";
			}
			elseif(in_array($this->status, array("Captured","Killed"), true)) {
				$status['class'] = "alert-danger";
			}
			
			array_push($statusInfo, $status);
		}
		
		if($this->promoted == true) {
			$status['label'] = "Promoted";
			$status['class'] = "alert-success";
			array_push($statusInfo, $status);
		}
		
		if($this->extra != null) {

            $query = "SELECT value from xcom_status_extra where id = :id";
            $params[0] = array("param" => ":id", "var" => $this->extra, "type" => PDO::PARAM_INT,);
            $queryResult = Database::runQuery('select', $query, $params);

            $row = $queryResult->fetch();

			$status['label'] = $row['value'];
			
			if($row['value'] == "Hacked") {
				$status['class'] = "alert-hacked";
			}
			elseif($row['value'] == "Mind Controlled" or $row['value'] == "Dominated") {
				$status['class'] = "alert-mind-controlled";
			}
			elseif($row['value'] == "Ambushed") {
				$status['class'] = "alert-danger";
			}
            else {
                $status['class'] = "alert-info";
            }
			array_push($statusInfo, $status);
		}
		
		return $statusInfo;
	}

    private function getParams(): array
    {
        $params[0] = array("param" => ":soldierID", "var" => $this->soldierID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":missionID", "var" => $this->missionID, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":rankID", "var" => $this->rankID, "type" => PDO::PARAM_INT,);
        $params[3] = array("param" => ":classID", "var" => $this->classID, "type" => PDO::PARAM_INT,);
        $params[4] = array("param" => ":shotsTaken", "var" => $this->shotsTaken, "type" => PDO::PARAM_INT,);
        $params[5] = array("param" => ":shotsHit", "var" => $this->shotsHit, "type" => PDO::PARAM_INT,);
        $params[6] = array("param" => ":overwatchTaken", "var" => $this->overwatchTaken, "type" => PDO::PARAM_INT,);
        $params[7] = array("param" => ":overwatchHit", "var" => $this->overwatchHit, "type" => PDO::PARAM_INT,);
        $params[8] = array("param" => ":otherTaken", "var" => $this->otherTaken, "type" => PDO::PARAM_INT,);
        $params[9] = array("param" => ":otherHit", "var" => $this->otherHit, "type" => PDO::PARAM_INT,);
        $params[10] = array("param" => ":damage", "var" => $this->damage, "type" => PDO::PARAM_INT,);
        $params[11] = array("param" => ":eas", "var" => strval($this->eas), "type" => PDO::PARAM_STR,);
        $params[12] = array("param" => ":healing", "var" => $this->healing, "type" => PDO::PARAM_INT,);
        $params[13] = array("param" => ":killedAliens", "var" => $this->killedAliens, "type" => PDO::PARAM_INT,);
        $params[14] = array("param" => ":killedLost", "var" => $this->killedLost, "type" => PDO::PARAM_INT,);
        $params[15] = array("param" => ":mvp", "var" => $this->mvp, "type" => PDO::PARAM_BOOL,);
        $params[16] = array("param" => ":status", "var" => $this->status, "type" => PDO::PARAM_STR,);
        $params[17] = array("param" => ":extra", "var" => $this->extra, "type" => PDO::PARAM_INT,);
        $params[18] = array("param" => ":extraInfo", "var" => $this->extraInfo, "type" => PDO::PARAM_STR,);
        $params[19] = array("param" => ":promoted", "var" => $this->promoted, "type" => PDO::PARAM_BOOL,);
        return $params;
    }

}