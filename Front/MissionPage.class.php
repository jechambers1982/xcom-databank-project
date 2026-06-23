<?php
declare(strict_types = 1);

namespace XCOMDatabank\Front;

use PDO;
use XCOMDatabank\Aliens\MOCX;
use XCOMDatabank\Missions\Mission;
use XCOMDatabank\Missions\MissionAlien;
use XCOMDatabank\Soldiers\Soldier;
use XCOMDatabank\Utility\Database;

class MissionPage
{
    private string $type;
    private ?int $id;
    private ?string $name;

    function __construct(string $type, ?int $id = null, ?string $name = null) {
        $this->type = $type;
        $this->id = $id;
        $this->name = $name;
    }

    public function getEpisode(): void {
        $mission = $this->getMission();

        $darkEvent = $mission->missionDarkEvent();
        $sitreps = $mission->missionSitreps();
        $missionInfo = $mission->missionInfo();

        $ratingStyle = $this->getRatingStyle($mission);
        $rewardStyle = $this->getRewardStyle($mission);
        $soldierInfo = $this->getSoldierInfo($mission);
        $alienInfo = $this->getAlienInfo($mission);

        $this->printPage($mission, $missionInfo, $sitreps, $darkEvent, $ratingStyle, $rewardStyle, $soldierInfo, $alienInfo);
    }

    private function getMission(): Mission {
        $mission = new Mission();

        if($this->type === "id" and $this->id === -1) {
            $query = "SELECT id FROM xcom_mission WHERE status != 3 ORDER BY id desc LIMIT 1";
            $params = array();
            $queryResult = Database::runQuery('select', $query, $params);
            $row = $queryResult->fetch();
            $mission->getMission(intval($row['id']));
        }
        elseif($this->type === "id") {
            $mission->getMission($this->id);
        }
        elseif($this->type === "name") {
            $opName = ucwords(str_replace("~"," ",strval($this->name)));
            $query = "SELECT id FROM xcom_mission WHERE operation_name = :name";
            $params[0] = array("param" => ":name", "var" => $opName, "type" => PDO::PARAM_STR,);

            $queryResult = Database::runQuery('select', $query, $params);
            $row = $queryResult->fetch();
            $mission->getMission(intval($row['id']));
        }
        return $mission;
    }

    private function getRatingStyle(Mission $mission): string {
        if($mission->rating == "Flawless" or $mission->rating == "Excellent") {
            $ratingStyle = "text-success";
        }
        elseif($mission->rating == "Good") {
            $ratingStyle = "text-muted";
        }
        elseif($mission->rating == "Fair") {
            $ratingStyle = "text-warning";
        }
        else {
            $ratingStyle = "text-danger";
        }
        return $ratingStyle;
    }

    private function getRewardStyle(Mission $mission): string {
        if($mission->status == "0" or $mission->status == "2") {
            $rewardStyle = "text-danger";
        }
        else {
            $rewardStyle = "text-success";
        }
        return $rewardStyle;
    }

    private function getSoldierInfo(Mission $mission): array {
        $query = "SELECT * FROM xcom_mission_soldier as sm
				INNER JOIN xcom_rank as rank ON rank.id = sm.rank_id
			WHERE sm.mission_id = ".$mission->id." ORDER BY sm.mvp desc, rank.level desc";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $soldierInfo['totalSoldiers'] = 0;
        $soldierInfo['killedSoldiers'] = 0;
        $soldierInfo['shakenSoldiers'] = 0;
        $soldierInfo['injuredSoldiers'] = 0;


        while ($row = $queryResult->fetch()) {
            $soldierInfo['totalSoldiers']++;

            if($row['status'] == "Killed" and $row['soldier_id'] != null) {
                $soldierInfo['killedSoldiers']++;
            }
            elseif($row['status'] == "Shaken" and $row['soldier_id'] != null) {
                $soldierInfo['shakenSoldiers']++;
            }
            elseif(($row['status'] == "Lightly Wounded" or $row['status'] == "Wounded" or $row['status'] == "Gravely Wounded") and $row['soldier_id'] != null) {
                $soldierInfo['injuredSoldiers']++;
            }
        }

        $woundedRatio = $soldierInfo['injuredSoldiers'] / $soldierInfo['totalSoldiers'];

        if($soldierInfo['killedSoldiers'] == 0) {
            $soldierInfo['soldiersKilledStyle'] = "text-success";
        }
        else {
            $soldierInfo['soldiersKilledStyle'] = "text-danger";
        }

        if($woundedRatio == 0) {
            $soldierInfo['woundedStyle'] = "text-success";
        }
        elseif($woundedRatio > 0 and $woundedRatio <= .25) {
            $soldierInfo['woundedStyle'] = "text-muted";
        }
        elseif($woundedRatio > .25 and $woundedRatio <= .5) {
            $soldierInfo['woundedStyle'] = "text-warning";
        }
        else {
            $soldierInfo['woundedStyle'] = "text-danger";
        }

        return $soldierInfo;
    }

    private function getAlienInfo(Mission $mission): array {
        // Get Alien Info
        $query = "SELECT alien.name, type.name, ma.encountered, ma.killed
			FROM xcom_mission_alien as ma
				INNER JOIN xcom_aliens as alien ON ma.alien_id = alien.id
				INNER JOIN xcom_alien_type as type on type.id = alien.type_id
			WHERE ma.mission_id = ".$mission->id." ORDER BY type.name, alien.name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        // Get MOCX Info
        $query = "SELECT status from xcom_mocx_mission WHERE mission_id = ".$mission->id;
        $queryResult2 = Database::runQuery('select', $query, $params);

        $alienInfo['encounteredAliens'] = 0;
        $alienInfo['killedAliens'] = 0;

        while ($row = $queryResult->fetch()) {
            $alienInfo['encounteredAliens'] += $row['encountered'];
            $alienInfo['killedAliens'] += $row['killed'];
        }

        while ($row = $queryResult2->fetch()) {
            if($row['status'] == "Killed")
                $alienInfo['killedAliens'] += + 1;
            $alienInfo['encounteredAliens'] += + 1;
        }

        $alienRatio = $alienInfo['killedAliens'] / $alienInfo['encounteredAliens'];

        if(($alienRatio) == 1) {
            $alienInfo['killedStyle'] = "text-success";
        }
        elseif($alienRatio < 1 and $alienRatio >= .75) {
            $alienInfo['killedStyle'] = "text-muted";
        }
        elseif($alienRatio < .75 and $alienRatio >= .5) {
            $alienInfo['killedStyle'] = "text-warning";
        }
        else {
            $alienInfo['killedStyle'] = "text-danger";
        }
        return $alienInfo;
    }

    private function printPage(Mission $mission, array $missionInfo, array $sitreps, string $darkEvent, string $ratingStyle,
                               string $rewardStyle, array $soldierInfo, array $alienInfo): void {
        ?>
        <div class="row equal-height mb-5">
            <div class="col-12 col-lg-6">
                <div class="card mission-top border-primary">
                    <div class="card-header">
                        <h2>Operation <?php echo $mission->operationName; ?></h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if($mission->status == "1") { ?>
                            <li class="list-group-item bg-success text-white mission-result">Mission Completed</li>
                        <?php } elseif($mission->status == "0" or $mission->status == "2") { ?>
                            <li class="list-group-item bg-danger text-white mission-result">Mission Failed</li>
                        <?php } ?>
                    </ul>
                    <img src="<?php echo $mission->picture; ?>" alt="Operation <?php echo $mission->operationName; ?>" class="card-img-bottom" />
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card border-success">
                    <div class="card-header mission-information">Mission Information</div>
                    <ul class="list-group list-group-flush">
                        <?php if($mission->is_infiltration == 0) { ?>
                            <li class="list-group-item assault-mission mi-list text-danger"><i class="fas fa-crosshairs fa-2x"></i>Assault Mission</li>
                        <?php } else { ?>
                            <li class="list-group-item infiltration-mission mi-list text-primary"><i class="fas fa-cog fa-2x"></i>Infiltration Mission</li>
                            <li class="list-group-item infiltration-amount mi-list"><i class="fas fa-cog fa-2x"></i>
                                <div class="progress infil-progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo round(($mission->infiltration / 250) * 100); ?>%" aria-valuenow="<?php echo round(($mission->infiltration / 250) * 100); ?>"  aria-valuemin="0" aria-valuemax="250"><?php echo $mission->infiltration; ?> %</div>
                                </div>
                            </li>
                        <?php } ?>
                        <li class="list-group-item mission-objective mi-list"><i class="fas fa-info-circle fa-2x"></i><?php echo $missionInfo['type'].": ".$missionInfo['objective']; ?></li>
                        <li class="list-group-item mission-location mi-list"><i class="fas fa-globe fa-2x"></i><?php echo $mission->location." (".$mission->sector.")"; ?></li>
                        <li class="list-group-item mission-date mi-list"><i class="far fa-calendar-alt fa-2x"></i><?php echo date('F d, Y', strtotime($mission->missionDate)); ?></li>
                        <li class="list-group-item mission-difficulty mi-list"><i class="fas fa-exclamation-triangle fa-2x text-warning"></i><?php echo $mission->difficulty; ?></li>
                        <?php if(join(", ",$sitreps) != "") { ?>
                            <li class="list-group-item mission-sitrep mi-list"><i class="fas fa-eye fa-2x"></i><?php echo join(", ",$sitreps); ?></li>
                        <?php } ?>
                        <?php if($darkEvent != "") { ?>
                            <li class="list-group-item mission-darkevent mi-list"><i class="fas fa-eye fa-2x"></i><?php echo join(", ",$sitreps); ?></li>
                        <?php } ?>
                        <li class="list-group-item episodes mi-list"><i class="fab fa-youtube fa-2x"></i>
                            <?php
                            for ($x = 0; $x < sizeof($mission->episode); $x++) {
                                if($x > 0) {
                                    echo " | ";
                                }
                                echo '<a href="'.$mission->url[$x].'" target="_blank" />Watch Episode '.$mission->episode[$x].'</a>';
                            }
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header mission-details">Mission Details</div>
                    <div class="row gx-0">
                        <div class="col-6 col-md-2 card-body details-title">Rating</div>
                        <div class="col-6 col-md-4 card-body details-info <?php echo $ratingStyle; ?>"><?php echo $mission->rating; ?></div>
                        <div class="col-6 col-md-2 card-body details-title">Enemies Killed</div>
                        <div class="col-6 col-md-4 card-body details-info <?php echo $alienInfo['killedStyle']; ?>"><?php echo $alienInfo['killedAliens']." / ".$alienInfo['encounteredAliens']; ?></div>
                        <div class="col-6 col-md-2 card-body details-title">Soldiers Wounded</div>
                        <div class="col-6 col-md-4 card-body details-info <?php echo $soldierInfo['woundedStyle']; ?>"><?php echo $soldierInfo['injuredSoldiers']; ?></div>
                        <div class="col-6 col-md-2 card-body details-title">Soldiers Killed</div>
                        <div class="col-6 col-md-4 card-body details-info <?php echo $soldierInfo['soldiersKilledStyle']; ?>"><?php echo $soldierInfo['killedSoldiers']; ?></div>
                        <div class="col-6 col-md-2 card-body details-title">Turns Taken</div>
                        <div class="col-6 col-md-4 card-body details-info"><?php echo $mission->turns; ?></div>
                        <div class="col-6 col-md-2 card-body details-title">Rewards</div>
                        <div class="col-6 col-md-4 card-body details-info <?php echo $rewardStyle; ?>">
                            <?php
                            if($mission->status != "1") { ?>
                            <del>
                                <?php 	}
                                echo join("<br />",$mission->reward);
                                if($mission->status != "1") { ?>
                            </del>
                        <?php	} ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        Soldier::missionSoldierList($mission->id, "squad");

        Soldier::missionSoldierList($mission->id, "other");

        MOCX::missionMOCXList($mission->id);
        ?>

        <div class="card page-head mission-alien-list-head">
            <div class="card-header bg-success text-white">Alien Information</div>
        </div>

        <?php MissionAlien::missionAlienList($mission->id);
    }
}