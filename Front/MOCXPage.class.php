<?php

namespace XCOMDatabank\Front;

use PDO;
use XCOMDatabank\Aliens\MOCX;
use XCOMDatabank\Utility\Database;

class MOCXPage
{
    private array $overall;

    public function __construct()
    {
        $this->overall['shots_taken'] = 0;
        $this->overall['shots_hit'] = 0;
        $this->overall['damage'] = 0;
        $this->overall['killed'] = 0;
        $this->overall['killed_others'] = 0;
        $this->overall['killed_lost'] = 0;
    }

    public function getMOCX(): void {
        $this->getListHead();

        $this->getListBody();

        $this->getListFooter();
    }

    private function getListHead(): void {
        ?>
        <div class="mocx-list-header-row gx-0 mb-3">
            <div class="mocx-list">
                <div class="head-content">Name</div>
                <div class="head-content">Class</div>
                <div class="head-content">Missions</div>
                <div class="head-content">Shots</div>
                <div class="head-content">Damage</div>
                <div class="head-content">Kills</div>
                <div class="head-content">Status</div>
            </div>
        </div>
        <?php
    }

    private function getListBody(): void {
        $query = "SELECT id FROM xcom_mocx ORDER BY FIELD(status,'active','captured','killed'), last_name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        while ($row = $queryResult->fetch()) {

            $mocx = new MOCX();
            $mocx->getMOCX($row['id']);

            $statsInfo = $mocx->MOCXStats();

            if($mocx->nickname == "")
                $mocxName = $mocx->firstName.' '.$mocx->lastName;
            else
                $mocxName = $mocx->firstName.' "'.$mocx->nickname.'" '.$mocx->lastName;

            if($statsInfo['shots_taken'] > 0) {
                $shotsText = $statsInfo['shots_hit'].'/'.$statsInfo['shots_taken'].'<span class="shot-pct"> ('.$statsInfo['shots_pct'].'%)</span>';
            } else {
                $shotsText = "";
            }

            $killedText = "";
            // If XCOM Soldiers Killed
            if($statsInfo['killed'] > 0) {
                $killedText = $statsInfo['killed']." (XCOM)";
            }

            // If Others Killed
            if($statsInfo['others'] > 0) {
                $killedText .= $killedText == "" ? '' : '<br />';
                $killedText .= $statsInfo['others']." (Other)";
            }

            // If Lost killed
            if($statsInfo['lost'] > 0) {
                $killedText .= $killedText == "" ? '' : '<br />';
                $killedText .= $statsInfo['lost']." (Lost)";
            }

            $killedText = $killedText == "" ? 0 : $killedText;

            $this->overall['shots_taken'] 	+= $statsInfo['shots_taken'];
            $this->overall['shots_hit'] 		+= $statsInfo['shots_hit'];
            $this->overall['damage']			+= $statsInfo['damage'];
            $this->overall['killed']			+= $statsInfo['killed'];
            $this->overall['killed_others']     += $statsInfo['others'];
            $this->overall['killed_lost']		+= $statsInfo['lost'];

            if($mocx->status == "Killed")
                $livingClass = " alert-danger border-danger border";
            elseif($mocx->status == "Captured")
                $livingClass = " alert-warnin border-warning border";
            else
                $livingClass = " alert-success border-success border";

            $query = "SELECT count(*) from xcom_mocx_mission WHERE mocx_id = :mocx";
            $params[0] = array("param" => ":mocx", "var" => $row['id'], "type" => PDO::PARAM_INT,);
            $queryResult2 = Database::runQuery('select', $query, $params);

            $missionCount = $queryResult2->fetchColumn();

            $missionText = $missionCount == 1 ? "1 Mission" : $missionCount." Missions";

            if($mocx->mocxClass == "Skirmisher") {
                $classIcon = "skirmishers";
            } elseif ($mocx->mocxClass == "Zealot") {
                $classIcon = "templars";
            } elseif ($mocx->mocxClass == "Psionic") {
                $classIcon = "exalt-psionic";
            } elseif ($mocx->mocxClass == "Phantom") {
                $classIcon = "reaper";
            } elseif ($mocx->mocxClass == "MEC Trooper") {
                $classIcon = "mec-trooper";
			} elseif ($mocx->mocxClass == "Rookie") {
                $classIcon = "rookie";
            } else {
                $classIcon = "exalt-".strtolower($mocx->mocxClass);
            }

    ?>
        <div class="mocx-list-row<?php echo $livingClass; ?> gx-0 mb-2">
                <div class="mocx-list">
                    <div class="list-row-content name"><?php echo $mocxName; ?></div>
                    <div class="list-row-content class classicon">
                        <img src="/img/class/<?php echo $classIcon; ?>.png" alt="<?php echo $mocx->mocxClass; ?>" title="<?php echo $mocx->mocxClass; ?>" height="32" width="32" />
                        <span><?php echo $mocx->mocxClass; ?></span>
                    </div>
                    <div class="list-row-content missions"><?php echo $missionText; ?></div>
                    <div class="list-row-content shots"><?php echo $shotsText; ?></div>
                    <div class="list-row-content damage"><?php echo $statsInfo['damage']; ?></div>
                    <div class="list-row-content kills"><?php echo $killedText; ?></div>
                    <div class="list-row-content status"><?php echo ucwords($mocx->status); ?></div>
                </div>
        </div>
    <?php
        }
    }

    private function getListFooter(): void {
        $this->overall['shots_pct'] = "N/A";

        if($this->overall['shots_taken'] > 0) {
            $this->overall['shots_pct'] = (round($this->overall['shots_hit'] / $this->overall['shots_taken'], 2)) * 100;
        }

        $this->overall['killed_text'] = "";
        if($this->overall['killed'] > 0) {
            $this->overall['killed_text'] .= $this->overall['killed']." (XCOM)";
        }

        if($this->overall['killed_others'] > 0) {
            $this->overall['killed_text'] .=  $this->overall['killed_text'] == "" ? '' : '<br />';
            $this->overall['killed_text'] .= $this->overall['killed_others']." (Others)";
        }

        elseif($this->overall['killed_lost'] > 0) {
            $this->overall['killed_text'] .=  $this->overall['killed_text'] == "" ? '' : '<br />';
            $this->overall['killed_text'] .= $this->overall['killed_lost']." (Lost)";
        }

        $this->overall['killed_text'] = $this->overall['killed_text'] == "" ? 0 : $this->overall['killed_text'];

        $query = "SELECT count(distinct mission_id) as count from xcom_mocx_mission";
        $params = array();
        $queryResult2 = Database::runQuery('select', $query, $params);

        $missionCount = $queryResult2->fetch();

        $missionText = $missionCount['count'] == 1 ? $missionCount['count']." Mission" : $missionCount['count']." Missions";
    ?>
        <div class="mocx-list-footer gx-0 mb-3">
            <div class="mocx-list alert-light border-secondary border">
                <div class="list-row-content name"><strong>Team Total</strong></div>
                <div class="list-row-content class"></div>
                <div class="list-row-content mission"><?php echo $missionText; ?></div>
                <div class="list-row-content shots"><strong><?php echo $this->overall['shots_hit']."/".$this->overall['shots_taken']." (".$this->overall['shots_pct']."%)"; ?></strong></div>
                <div class="list-row-content damage"><strong><?php echo $this->overall['damage']; ?></strong></div>
                <div class="list-row-content kills"><strong><?php echo $this->overall['killed_text']; ?></strong></div>
                 <div class="list-row-content status"></div>
            </div>
        </div>
    <?php
    }
}