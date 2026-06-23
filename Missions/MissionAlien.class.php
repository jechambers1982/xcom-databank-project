<?php
declare(strict_types = 1);

namespace XCOMDatabank\Missions;

use PDO;
use XCOMDatabank\Aliens\Alien;
use XCOMDatabank\Aliens\AlienType;
use XCOMDatabank\Forms\Field;
use XCOMDatabank\Forms\FieldHidden;
use XCOMDatabank\Forms\FieldSelect;
use XCOMDatabank\Forms\FieldTextNum;
use XCOMDatabank\Utility\Database;
use XCOMDatabank\Utility\Error;
use XCOMDatabank\Utility\Validate;

class MissionAlien
{
	public ?int $id;
	public int $missionID;
	public int $alienID;
	public int $encountered;
	public int $killed;
		
	function __construct() {
        $this->id = null;
		$this->missionID = 0;
		$this->alienID = 1;
		$this->encountered = 0;
		$this->killed = 0;
	}
		
	public function newMissionAlien(array $missionAlien)
	{
        $this->validateMissionAlien($missionAlien);

        $query = "INSERT INTO xcom_mission_alien VALUES (NULL, :missionID, :alienID, :encountered, :killed)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
		
	public function getMissionAlien(int $id)
	{
        $query = "SELECT * FROM xcom_mission_alien WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();
		
		$this->id = $id;
		$this->missionID = intval($row['mission_id']);
		$this->alienID = intval($row['alien_id']);
		$this->encountered = intval($row['encountered']);
		$this->killed = intval($row['killed']);
	}
		
	public function editMissionAlien(array $missionAlien)
	{
        $this->validateMissionAlien($missionAlien);

        $query = "UPDATE xcom_mission_alien SET mission_id = :missionID, alien_id = :alienID, encountered = :encountered, killed = :killed WHERE id = :id";
        $params = $this->getParams();
        $params[4] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateMissionAlien(array $submit): void {
        // If a Mission Alien ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex((int)$submit['id'], 'mission_alien', false, "Mission Alien ID");
        }

        // Make sure Mission ID exists and is valid
        $this->missionID = Validate::testIndex((int)$submit['mission_id'], 'mission', false, "Mission ID");

        // Make sure Alien ID exists and is valid
        $this->alienID = Validate::testIndex((int)$submit['alien_id'], 'aliens', false, "Alien ID");

        // Make sure Aliens Encountered is an Integer
        $this->encountered = Validate::testInteger((int)$submit['encountered'], 0, 100, false, "Aliens Encountered");

        // Make sure Aliens Killed is an Integer
        $this->killed = Validate::testInteger((int)$submit['killed'], 0, 100, false, "Aliens Killed");
    }

    public function processForm(array $submit, string $redirect = ''): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editMissionAlien($submit);
            } else {
                return Error::returnError("Mission Type ID is set, but Mission Type ID is not numeric");
            }
        } else {
            $this->newMissionAlien($submit);
        }
        if(!empty($redirect)) {
            header('Location: '.$redirect);
        }
        return "";
    }

    public static function getMissionAlienForm(MissionAlien $missionAlien, bool $multi, bool $lastElement) {
        $nameA = "";
        if($multi) {
            $nameA = "[]";
        }

        $id = $missionAlien->id;
        if($id != "" and $id !== null and is_numeric($id)) {
            echo FieldHidden::getField('id'.$nameA,strval($id));
        }

        if(!$multi) {
            echo FieldSelect::getField('mission_id'.$nameA, strval($missionAlien->missionID), 'form-control', 'mission-id',
                'Mission', array('col-md-4','pb-2','pe-3','form-floating'), true, Mission::getMissionArray());
        }

        $alienType = AlienType::getAlienTypeByAlien($missionAlien->alienID);

        echo FieldSelect::getField('baseAlien'.$nameA, strval($alienType), 'form-control typeid', 'base-alien',
            'Alien Type', array('col-md-4','pb-2','pe-3','form-floating'), true, AlienType::getAlienTypeArray());

        echo FieldSelect::getField('alien_id'.$nameA, strval($missionAlien->alienID), 'form-control alienid', 'alien',
            'Alien', array('col-md-5','pb-2','pe-3','form-floating'), true, Alien::getAliensArrayByType($alienType));

        //Have to do special stuff for Aliens Encountered/Killed
        echo '<div class="pe-3 pb-2 mt-2 col-md-2">'."\n";
        echo '<div><strong>Killed</strong></div>'."\n";
        echo '<div class="input-group">'."\n";
        echo FieldTextNum::getField('killed'.$nameA, strval($missionAlien->killed), 'form-control', 'killed','', array(), true, false,'0', '100');
        echo '<span class="input-group-text">/</span>'."\n";
        echo FieldTextNum::getField('encountered'.$nameA, strval($missionAlien->encountered), 'form-control', 'encountered','', array(), true, false,'0', '100');
        echo '</div>'."\n";
        echo '</div>'."\n";

        if($multi) {
            echo Field::repeatButton($lastElement);
        }
    }

    public static function getListPage(): void {
        $query = "SELECT missionAlien.id as id, missionAlien.killed as killed, missionAlien.encountered as encountered, alienType.name as type, 
                    alien.name as name, mission.operation_name as operation
			FROM xcom_mission_alien as missionAlien 
				INNER JOIN xcom_aliens as alien ON missionAlien.alien_id = alien.id 
				INNER JOIN xcom_alien_type as alienType ON alien.type_id = alienType.id
                INNER JOIN xcom_mission as mission ON missionAlien.mission_id = mission.id
			ORDER BY CAST(mission.episode AS UNSIGNED) DESC, mission.operation_name, alienType.name, alien.name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table mission-soldier-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Mission</div>'."\n";
        $listString .= '<div class="col-3">Alien Type</div>'."\n";
        $listString .= '<div class="col-3">Alien</div>'."\n";
        $listString .= '<div class="col-2">Killed</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {

            $listString .= '<div class="row">'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['operation'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= $row['type'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-3">';
            $listString .= '<a href="/mission/mission-alien.php?id='.$row['id'].'">'.$row['name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-2">';
            $listString .= $row['killed'].'/'.$row['encountered'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";
        }
        $listString .= '</div>'."\n";

        echo $listString;
    }

    private function getParams(): array {
        $params[0] = array("param" => ":missionID", "var" => $this->missionID, "type" => PDO::PARAM_INT,);
        $params[1] = array("param" => ":alienID", "var" => $this->alienID, "type" => PDO::PARAM_INT,);
        $params[2] = array("param" => ":encountered", "var" => $this->encountered, "type" => PDO::PARAM_INT,);
        $params[3] = array("param" => ":killed", "var" => $this->killed, "type" => PDO::PARAM_INT,);
        return $params;
    }

    public static function missionAlienList(int $id): void {
        $query = "SELECT ma.killed, ma.encountered, alien.name as alien, type.name as type 
			FROM xcom_mission_alien as ma 
				INNER JOIN xcom_aliens as alien ON alien.id = ma.alien_id
				INNER JOIN xcom_alien_type as type ON type.id = alien.type_id
			WHERE ma.mission_id = :id
			ORDER BY CASE 
				when type.name = 'Chosen' then 1
				when type.name = 'Alien Ruler' then 2
				when type.name = 'The Lost' then 4
				else 3
			end, type.name, ma.encountered desc, alien.name";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);

        $overall['encountered'] = 0;
        $overall['killed'] = 0;
        $bigAlien = false;
        ?>
        <div class="row">
        <?php
        while ($row = $queryResult->fetch()) {
            $overall['encountered'] += $row['encountered'];
            $overall['killed'] += $row['killed'];
            $overall['pct'] = (round($overall['killed'] / $overall['encountered'], 2)) * 100;

            $killedPct = (round($row['killed'] / $row['encountered'], 2)) * 100;

            if($row['type'] == "Chosen") {
                $bigAlien = true;
            ?>
                            <div class="col-12 col-md-6 col-lg-4 mb-3">
                                <div class="card border-danger border-2">
                                    <div class="row gx-0">
                                        <div class="col-7 alien-name px-4 py-3 bg-chosen-mission"><?php echo $row['alien']; ?></div>
                                        <div class="col-5 alien-result px-4 py-3"><?php echo $row['killed']." / ".$row['encountered']; ?></div>
                                    </div>
                                </div>
                            </div>
            <?php
            }
            elseif($row['type'] == "Alien Ruler") {
                $bigAlien = true;
            ?>
                    <div class="col-12 col-md-6 col-lg-4 mb-3">
                        <div class="card border-primary border-2">
                            <div class="row gx-0">
                                <div class="col-7 alien-name px-4 py-3 bg-ruler-mission"><?php echo $row['alien']; ?></div>
                                <div class="col-5 alien-result px-4 py-3"><?php echo $row['killed']." / ".$row['encountered']; ?></div>
                            </div>
                        </div>
                    </div>
            <?php
            } else {
            ?>
                            <div class="col-12 col-md-6 col-lg-4 mb-3">
						        <div class="card border-secondary border-2">
							        <div class="row gx-0">
								        <div class="col-7 alien-name px-4 py-3 bg-light"><?php echo $row['alien']; ?></div>
								        <div class="col-5 alien-result px-4 py-3"><?php echo $row['killed']." / ".$row['encountered']; ?></div>
							        </div>
						        </div>
					        </div>
            <?php
            }
        }
        ?>
                            <div class="col-12 col-md-6 col-lg-4 mb-3">
                                <div class="card border-success">
                                    <div class="row gx-0">
                                        <div class="col-6 alien-name-totals px-4 py-3">Total Aliens</div>
                                        <div class="col-6 alien-totals px-4 py-3"><?php echo $overall['killed']." / ".$overall['encountered']." (".$overall['pct']."%)"; ?></div>
                                    </div>
                                </div>
                            </div>
        <?php
        if($bigAlien) {
        ?>
                        </div>
        <?php
        }
        ?>
                </div>
    <?php
    }
}