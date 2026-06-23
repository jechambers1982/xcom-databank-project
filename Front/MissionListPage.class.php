<?php
declare(strict_types = 1);

namespace XCOMDatabank\Front;

use PDO;
use XCOMDatabank\Missions\Mission;
use XCOMDatabank\Utility\Database;

class MissionListPage
{
    function __construct() {
    }

    public function getMissionList(): void {
        $this->getInfiltrations();

        $this->getMissionBoxes();
    }

    private function getInfiltrations(): void {
        $query = "SELECT id from xcom_mission WHERE status = 4 ORDER BY id";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        if($queryResult->rowCount() > 0) {
        ?>
            <div class="card page-head mission-list-head">
                <div class="card-header bg-success text-white">Currently Infiltrating Missions</div>
            </div>
            <div class="row">

        <?php
            while ($row = $queryResult->fetch()) {
                $mission = new Mission;
                $mission->getMission((int)$row['id']);
                $missionInfo = $mission->missionInfo();
        ?>
                    <div class="col-lg-3 col-md-6 col-sm-12 mission-box-container">
                        <div class="card mission-box mission-infiltrating border-primary">
                            <div class="card-header bg-primary text-white">
                                <strong>Operation <?php echo $mission->operationName; ?></strong>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Infiltrating <i class="fa-solid fa-gear fa-spin text-primary"></i></strong></li>
                                <li class="list-group-item operation"><strong><?php echo $missionInfo['type']; ?></strong><br />
                                    <?php echo $missionInfo['objective']; ?></li>
                                <li class="list-group-item mission-location detail-list"><i class="fas fa-globe fa-2x"></i><?php echo $mission->sector; ?></li>
                            </ul>
                        </div>
                    </div>
        <?php
            }
       ?>
            </div>
       <?php
        }
    }

    private function getMissionBoxes(): void {
        $query = "SELECT id from xcom_mission WHERE status != 4 ORDER BY mission_date DESC, episode DESC, id DESC";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        ?>
        <div class="card page-head mission-list-head">
            <div class="card-header bg-success text-white">Completed Missions</div>
        </div>
        <div class="row">
        <?php
        foreach($queryResult as $row) {
            $mission = new Mission;
            $mission->getMission(intval($row['id']));
            $missionInfo = $mission->missionInfo();

            $missionKilled = "";
            $killedString = "Killed";

            $missionRating = $mission->rating == "Flawless" ? "bg-success text-white" : "alert-info";
            $missionRating = $mission->rating == "Excellent" ? "bg-info text-white" : $missionRating;
            $missionRating = $mission->rating == "Good" ? "bg-secondary text-white" : $missionRating;
            $missionRating = $mission->rating == "Fair" ? "bg-warning text-white" : $missionRating;
            $missionRating = $mission->rating == "Poor" ? "bg-danger text-white" : $missionRating;


            if($mission->is_infiltration == 0) {
                $missionType = "Assault";
                $missionTypeIcon = "fa-crosshairs";
                $missionTypeStyle = "text-danger";
            }
            else
            {
                $missionType = "Infiltration";
                $missionTypeIcon = "fa-gear";
                $missionTypeStyle = "text-primary";
            }

            $query = "SELECT id from xcom_mission_soldier WHERE mission_id = :mission and status = :status";
            $params[0] = array("param" => ":mission", "var" => $mission->id, "type" => PDO::PARAM_INT,);
            $params[1] = array("param" => ":status", "var" => $killedString, "type" => PDO::PARAM_STR,);
            $queryResult = Database::runQuery('select', $query, $params);

            if ($queryResult->rowCount() > 0) {
                $missionKilled = "mission-killed";
            }

            if($mission->status == 1) {
                $missionComplete = "mission-completed";
            } else {
                $missionComplete = "mission-failed";
            }
        ?>
                <div class="col-lg-4 col-md-6 col-sm-12 mission-box-container rating-<?php echo strtolower($mission->rating); ?><?php echo " ".$missionKilled; ?>">
                    <div class="card mission-box <?php echo $missionComplete; ?>">
                        <div class="card-header">
                            <strong><a href="<?php echo "/mission/".strtolower(str_replace(" ","~",$mission->operationName))."/"; ?>">Operation <?php echo $mission->operationName; ?></a></strong>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item <?php echo $missionTypeStyle; ?>"><strong><?php echo $missionType; ?> Mission <i class="fa-solid <?php echo $missionTypeIcon; ?>"></i></strong></li>
                            <li class="list-group-item operation"><strong><?php echo $missionInfo['type']; ?></strong><br />
                                <?php echo $missionInfo['objective']; ?></li>
                            <li class="list-group-item episodes detail-list"><i class="fa-brands fa-youtube fa-2x"></i><?php
                                for ($x = 0; $x < sizeof($mission->episode); $x++) {
                                    if($x > 0) {
                                        echo " | ";
                                    }
                                    echo '<a href="'.$mission->url[$x].'" target="_blank" />Episode '.$mission->episode[$x].'</a>';
                                }
                                ?>
                            </li>
                        </ul>
                        <img src="<?php echo $mission->picture; ?>" alt="Operation <?php echo $mission->operationName; ?>" class="card-img" />
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item mission-date detail-list"><i class="fa-solid fa-calendar-days fa-2x"></i><?php echo date('F d, Y', strtotime($mission->missionDate)); ?></li>
                            <li class="list-group-item mission-location detail-list"><i class="fa-solid fa-globe fa-2x"></i><?php echo $mission->sector; ?></li>
                            <li class="list-group-item mission-objective <?php echo $missionRating; ?>"><strong>Mission Rating: <?php echo $mission->rating; ?></strong></li>
                        </ul>
                    </div>
                </div>

        <?php
        }
        ?>
        </div>

        <?php
    }
}