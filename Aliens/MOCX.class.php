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

class MOCX
{
	
	// MOCX ID for unique identification
	public ?int $id;
	
	// Basic MOCX Information
	public string $firstName;
	public string $lastName;
	public ?string $nickname;
	public string $mocxClass;
	public string $status;
	
	function __construct() {
        $this->id = null;
		$this->firstName = "";
		$this->lastName = "";
		$this->nickname = null;
        $this->mocxClass = "";
		$this->status = "";
	}
	
	public function newMOCX($mocx) {
        $this->validateMOCX($mocx);

        $query = "INSERT INTO xcom_mocx VALUES (NULL, :firstName, :lastName, :nickname, :class, :status)";
        $params = $this->getParams();

        $queryResult = Database::runQuery('insert', $query, $params);

        $this->id = $queryResult;
	}
	
	public function getMOCX(int $id) {
        $query = "SELECT * FROM xcom_mocx WHERE id = :id";
        $params[0] = array("param" => ":id", "var" => $id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
        $row = $queryResult->fetch();

        $this->id = $id;
        $this->firstName = $row['first_name'];
        $this->lastName = $row['last_name'];
        $this->nickname = $row['nickname'];
        $this->mocxClass = $row['class'];
        $this->status = $row['status'];
	}
	
	public function editMOCX($mocx)
	{
        $this->validateMOCX($mocx);

        $query = "UPDATE xcom_mocx SET first_name = :firstName, last_name = :lastName, nickname = :nickname, class = :class, status = :status WHERE id = :id";
        $params = $this->getParams();
        $params[5] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        Database::runQuery('update', $query, $params);
	}

    private function validateMOCX(array $submit): void {
        // If an MOCX ID was submitted, make sure it is valid
        if(isset($submit['id'])) {
            $this->id = Validate::testIndex($submit['id'], 'mocx', false, "MOCX ID");
        }

        // MOCX First Name
        $this->firstName = Validate::testString($submit['first_name'], -1, -1, false, "MOCX First Name");

        // MOCX Last Name
        $this->lastName = Validate::testString($submit['last_name'], -1, -1, false, "MOCX Last Name");

        // MOCX Nickname
        $this->nickname = Validate::testString($submit['nickname'], -1, -1, true, "MOCX Nickname");

        // MOCX Class
        $this->mocxClass = Validate::testArray($submit['mocx_class'], Definitions::getMOCXClass(), false, "MOCX Class");

        // MOCX Status
        $this->status = Validate::testArray($submit['mocx_status'], Definitions::getMOCXStatus(), false, "MOCX Status");
    }

    public function getParams(): array {
        $params[0] = array("param" => ":firstName", "var" => $this->firstName, "type" => PDO::PARAM_STR,);
        $params[1] = array("param" => ":lastName", "var" => $this->lastName, "type" => PDO::PARAM_STR,);
        $params[2] = array("param" => ":nickname", "var" => $this->nickname ?? null, "type" => PDO::PARAM_STR,);
        $params[3] = array("param" => ":class", "var" => $this->mocxClass, "type" => PDO::PARAM_STR,);
        $params[4] = array("param" => ":status", "var" => $this->status, "type" => PDO::PARAM_STR,);
        return $params;
    }

    public function processForm(array $submit, string $redirect): string {
        if(isset($submit['id'])) {
            if(is_numeric($submit['id'])) {
                $this->editMOCX($submit);
            } else {
                return Error::returnError("MOCX ID is set, but MOCX ID is not numeric");
            }
        } else {
            $this->newMOCX($submit);
        }
        header('Location: '.$redirect);
        return "";
    }

    public static function getMOCXForm(MOCX $mocx)
    {
        if (!empty($mocx->id) and is_numeric($mocx->id)) {
            echo FieldHidden::getField('id', strval($mocx->id));
        }

        echo FieldText::getField('first_name', $mocx->firstName, 'form-control', 'first-name',
            'First Name', array('col-md-4', 'pb-2', 'form-floating'), true);

        echo FieldText::getField('nickname', $mocx->nickname, 'form-control', 'nickname',
            'Nickname', array('col-md-4', 'pb-2', 'form-floating'), false);

        echo FieldText::getField('last_name', $mocx->lastName, 'form-control', 'last-name',
            'Last Name', array('col-md-4', 'pb-2', 'form-floating'), true);


        echo FieldSelect::getField('mocx_class', $mocx->mocxClass, 'form-control', 'mocx-class',
            'Class', array('col-md-6', 'pb-2', 'form-floating'), true, Definitions::getMOCXClass());

        echo FieldSelect::getField('mocx_status', $mocx->status, 'form-control', 'mocx-status',
            'Status', array('col-md-6', 'pb-2', 'form-floating'), true, Definitions::getMOCXStatus());
    }

    public static function getListPage(): void {
        $query = "SELECT * FROM xcom_mocx ORDER BY FIELD(status,'active','captured','killed'), last_name";
        $params = array();

        $queryResult = Database::runQuery('select', $query, $params);

        $listString = '<div class="container admin-table rank-list">'."\n";
        $listString .= '<div class="row head-row">'."\n";
        $listString .= '<div class="col-4">Name</div>'."\n";
        $listString .= '<div class="col-4">Class</div>'."\n";
        $listString .= '<div class="col-4">Status</div>'."\n";
        $listString .= '</div>'."\n";

        foreach ($queryResult as $row) {
            if($row['status'] == "Active") {
                $enabledClass = "row-green";
            } elseif($row['status'] == "Killed") {
                $enabledClass = "row-red";
            } else {
                $enabledClass = "row-yellow";
            }

            $listString .= '<div class="row '.$enabledClass.'">'."\n";
            $listString .= '<div class="col-4">';
            $listString .= '<a href="/aliens/mocx.php?id='.$row['id'].'">'.$row['first_name'].' "'.$row['nickname'].'" '.$row['last_name'].'</a>';
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['class'];
            $listString .= '</div>'."\n";

            $listString .= '<div class="col-4">';
            $listString .= $row['status'];
            $listString .= '</div>'."\n";
            $listString .= '</div>'."\n";

        }
        $listString .= '</div>'."\n";

        echo $listString;
    }
	
	public function mocxStats(): array {
        $query = "SELECT SUM(mm.shots_taken) as shotsTaken, SUM(mm.shots_hit) as shotsHit, SUM(mm.damage) as damage, SUM(mm.killed) as killed, SUM(mm.killed_others) as others, SUM(mm.killed_lost) as lost
			FROM xcom_mocx_mission as mm
				INNER JOIN xcom_mocx as mocx ON mocx.id = mm.mocx_id
			WHERE mocx.id = :id";
        $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);

        $queryResult = Database::runQuery('select', $query, $params);
		$row = $queryResult->fetch();
		
		$statsInfo['shots_taken'] = $row['shotsTaken'];
		$statsInfo['shots_hit'] = $row['shotsHit'];
		if($row['shotsTaken'] > 0) {
			$statsInfo['shots_pct'] = (round($row['shotsHit'] / $row['shotsTaken'], 2)) * 100;
		} else {
			$statsInfo['shots_pct'] = "0";
		}
		
		$statsInfo['damage'] = $row['damage'];
		
		$statsInfo['killed'] = $row['killed'];
        $statsInfo['others'] = $row['others'];
		$statsInfo['lost'] = $row['lost'];
		$statsInfo['total_killed'] = $row['killed'] + $row['lost'];

		return $statsInfo;
	}

    public static function getMOCXList(): array {
        $query = "SELECT id, first_name, last_name, nickname FROM xcom_mocx ORDER BY status, last_name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $mocxArray = array();

        foreach ($queryResult as $item) {
            $newArray = array(
                'text' => $item['first_name'].' "'.$item['nickname'].'" '.$item['last_name'],
                'value' => $item['id'],
            );
            array_push($mocxArray, $newArray);
        }

        return $mocxArray;
    }

    public static function missionMOCXList(int $missionID): void {
        $query = "SELECT mocx.id, mocx.first_name, mocx.nickname, mocx.last_name, mocx.class, mm.status, mm.shots_taken, mm.shots_hit, mm.damage, mm.killed, mm.killed_others as others, mm.killed_lost as lost
			FROM xcom_mocx_mission as mm
			LEFT JOIN xcom_mocx as mocx on mocx.id = mm.mocx_id
			WHERE mm.mission_id = :id
			ORDER BY status, mocx.last_name";
        $params[0] = array("param" => ":id", "var" => $missionID, "type" => PDO::PARAM_INT,);
        $queryResult = Database::runQuery('select', $query, $params);

        if($queryResult->rowCount() > 0) {
    ?>
        <div class="card bg-danger text-white mb-3">
            <div class="card-header">
                <h2>EXALT Information</h2>
            </div>
        </div>
        <div class="card-deck mb-3">
            <div class="card summation">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3"><strong>Name</strong></div>
                        <div class="col-1"></div>
                        <div class="col-1"><strong>Shots</strong></div>
                        <div class="col-1"><strong>Damage</strong></div>
                        <div class="col-2"><strong>Kills</strong></div>
                        <div class="col-2"><strong>Mission #</strong></div>
                        <div class="col-2"><strong>Status</strong></div>
                    </div>
                </div>
    <?php
            while ($row = $queryResult->fetch()) {

                $query = "SELECT count(*) from xcom_mocx_mission WHERE mocx_id = :mocx";
                $params[0] = array("param" => ":mocx", "var" => $row['id'], "type" => PDO::PARAM_INT,);
                $queryResult2 = Database::runQuery('select', $query, $params);

                $missionCount = $queryResult2->fetchColumn();

                if($missionCount == 1)
                    $missionText = "1st";
                elseif($missionCount == 2)
                    $missionText = "2nd";
                elseif($missionCount == 3)
                    $missionText = "3rd";
                else
                    $missionText = $missionCount."th";

                $missionText .= " Mission";

                $mocxname = $row['first_name'];
                if($row['nickname'] != "" and $row['nickname'] != null)
                    $mocxname = $mocxname.' "'.$row['nickname'].'"';
                $mocxname = $mocxname." ".$row['last_name'];

                $killedText = "";
                if($row['killed'] > 0) {
                    $killedText .= $row['killed'].' (XCOM)';
                }

                if($row['others'] > 0) {
                    $killedText .= $killedText == '' ? '' : '<br />';
                    $killedText .= $row['others'].' (Others)';
                }

                if($row['lost'] > 0) {
                    $killedText .= $killedText == '' ? '' : '<br />';
                    $killedText .= $row['lost'].' (Lost)';
                }

                $killedText = $killedText == '' ? 0 : $killedText;

                if($row['status'] == "Active" or $row['status'] == "Evacuated")
                    $statusClass = "text-success";
                elseif($row['status'] == "Killed" or $row['status'] == "Captured")
                    $statusClass = "text-danger";
                elseif($row['status'] == "Bleed Out (Evac)")
                    $statusClass = "text-info";
                else
                    $statusClass = "";

                if($row['class'] == "Skirmisher") {
                    $classIcon = "skirmishers";
                } elseif ($row['class'] == "Zealot") {
                    $classIcon = "templars";
                } elseif ($row['class'] == "Psionic") {
                    $classIcon = "exalt-psionic";
				} elseif ($row['class'] == "Phantom") {
                    $classIcon = "reaper";
				} elseif ($row['class'] == "MEC Trooper") {
                    $classIcon = "mec-trooper";
				} elseif ($row['class'] == "Rookie") {
                    $classIcon = "rookie";
                } else {
                    $classIcon = 'exalt-'.strtolower($row['class']);
                }
    ?>
                <div class="list-group-item">
					<div class="row <?php echo $statusClass; ?>">
						<div class="col-3"><?php echo $mocxname; ?></div>
						<div class="col-1"><img src="/img/class/<?php echo $classIcon; ?>.png" alt="<?php echo $row['class']; ?>" width="32" /></div>							<div class="col-1"><?php echo $row['shots_hit']."/".$row['shots_taken']; ?></div>
						<div class="col-1"><?php echo $row['damage']; ?></div>
						<div class="col-2"><?php echo $killedText; ?></div>
						<div class="col-2"><?php echo $missionText; ?></div>
						<div class="col-2"><?php echo ucwords($row['status']); ?></div>
					</div>
				</div>
    <?php
            }
    ?>
            </div>
        </div>
    <?php
        }
    }
}