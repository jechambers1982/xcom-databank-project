<?php
declare(strict_types = 1);

namespace XCOMDatabank\Front;

use PDO;
use XCOMDatabank\Covert\CovertAction;
use XCOMDatabank\Covert\CovertType;
use XCOMDatabank\Utility\Database;

class CovertPage
{
    private bool $firstInProgress;
    private bool $firstComplete;
    function __construct()
    {
        $this->firstInProgress = true;
        $this->firstComplete = true;
    }

    public function getCovertList(): void {
        $query = "SELECT * from xcom_covert_action ORDER BY NOT FIELD (status, 'Ongoing'), `end_date` DESC";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        while ($row = $queryResult->fetch()) {
            $covertAction = new CovertAction();
            $covertAction->getCovertAction(intval($row['id']));
            $covertType = new CovertType();
            $covertType->getCovertType($covertAction->type);

            $covertFaction = $covertAction->faction == "Reapers" ? "covert-reapers" : '';
            $covertFaction = $covertAction->faction == "Skirmishers" ? "covert-skirmishers" : $covertFaction;
            $covertFaction = $covertAction->faction == "Templars" ? "covert-templars" : $covertFaction;

            $query = "SELECT operative.*, soldier.first_name, soldier.nickname, soldier.last_name 
				FROM xcom_covert_operative as operative 
					LEFT JOIN xcom_soldier as soldier ON operative.soldier_id = soldier.id
				WHERE action_id = :id ORDER BY id";
            $params[0] = array("param" => ":id", "var" => $covertAction->id, "type" => PDO::PARAM_INT,);
            $operativeResult = Database::runQuery('select', $query, $params);

            $missionType = $covertType->name;

            $opStatus = $covertAction->status == "Complete" ? "bg-success text-white" : '';
            $opStatus = $covertAction->status == "In Progress" ? "bg-info text-white" : $opStatus;
            $opStatus = $covertAction->status == "Ambushed" ? "bg-danger text-white" : $opStatus;

            if($covertAction->status == "In Progress" and $this->firstInProgress) {
        ?>

                <div class="card page-head covert-list-head">
                    <div class="card-header bg-success text-white">In Progress Covert Actions</div>
                </div>
                <div class="row">

        <?php
                $this->firstInProgress = false;
            }
            elseif($covertAction->status != "In Progress" and $this->firstComplete) {
                if($this->firstInProgress == false) {
        ?>
                </div>
        <?php
                }
        ?>
                <div class="card page-head covert-list-head">
                    <div class="card-header bg-success text-white">Completed Covert Actions</div>
                </div>
                <div class="row">
        <?php
                $this->firstComplete = false;
            }
        ?>
            <div class="col-lg-4 col-md-6 col-sm-12 covert-box-container">
                <div class="card covert-box <?php echo $covertFaction; ?>">
                    <div class="card-header"><?php echo $missionType; ?></div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item covert-mission"><strong><?php echo $covertType->mission; ?>:</strong><br /><?php echo $covertAction->reward; ?></li>

                        <?php
                        if($covertAction->status == "In Progress") { ?>
                            <li class="list-group-item covert-status bg-primary text-white"><strong>Status:</strong> In Progress <i class="fa-solid fa-gear fa-spin"></i></li>
                            <li class="list-group-item covert-date"><?php echo date('M d, Y', strtotime($covertAction->startDate))." - TBD" ?></li>
                            <?php
                        } else { ?>
                            <li class="list-group-item covert-status <?php echo $opStatus; ?>"><strong>Status: <?php echo $covertAction->status; ?></strong></li>
                            <li class="list-group-item covert-date"><?php echo date('M d, Y', strtotime($covertAction->startDate))." - ".date('M d, Y', strtotime($covertAction->endDate)); ?></li>
                            <?php
                        } ?>
                        <li class="list-group-item covert-location"><?php echo $covertAction->location; ?></li>
        <?php
            while($operative = $operativeResult->fetch()) {
                $requirement = $operative['requirement'];
                if (strpos($operative['requirement'], '+') !== false) {
                    $strpos = strpos($operative['requirement'], '+');
                    $requirement = substr($operative['requirement'], 0, $strpos);
                }

                $reqClass = "req-".str_replace(" ","-",strtolower($requirement));

                if(is_numeric($operative['soldier_id'])) {
                    $operativeString = '<a href="/soldier/'.str_replace(" ","-",strtolower($operative['first_name'])).'~'.str_replace(" ","-",strtolower($operative['last_name'])).'">'.$operative['first_name'].' "'.$operative['nickname'].'" '.$operative['last_name'].'</a>';
                } else {
                    $operativeString = $operative['resource'];
                    if($operative['requirement'] == "Alien Alloys" or
                        $operative['requirement'] == "Elerium Crystals" or
                        $operative['requirement'] ==  "Intel" or
                        $operative['requirement'] == "Supplies")
                    {
                        $operativeString .= ' '.$operative['requirement'];
                    }
                }
                if($operative['reward'] != "") {
                    $operativeString = $operativeString.'<br />'.$operative['reward'];
                }
                if($operative['status'] == "Wounded") {
                    $reqClass = $reqClass.' op-wounded';
                    $operativeString = $operativeString.'<br /><span class="text-warning">Wounded</span>';
                }
                elseif($operative['status'] == "Captured") {
                    $reqClass = $reqClass.' op-captured';
                    $operativeString = $operativeString.'<br /><span class="text-danger">Captured</span>';
                }
                elseif($operative['status'] == "Killed") {
                    $reqClass = $reqClass.' op-killed';
                    $operativeString = $operativeString.'<br /><span class="text-danger">Killed</span>';
                }
        ?>
                            <li class="list-group-item covert-operative <?php echo $reqClass; ?>"><?php echo $operativeString; ?></li>
        <?php
            }
        ?>
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