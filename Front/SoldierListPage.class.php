<?php
declare(strict_types = 1);

namespace XCOMDatabank\Front;

use XCOMDatabank\Soldiers\Soldier;
use XCOMDatabank\Utility\Database;

class SoldierListPage
{
    public array $overall;

    function __construct() {
        $this->overall['shots_taken'] = 0;
        $this->overall['shots_hit'] = 0;
        $this->overall['overwatch_taken'] = 0;
        $this->overall['overwatch_hit'] = 0;
        $this->overall['other_taken'] = 0;
        $this->overall['other_hit'] = 0;
        $this->overall['damage'] = 0;
        $this->overall['aliens_killed'] = 0;
        $this->overall['lost_killed'] = 0;
        $this->overall['eas'] = 0.00;
    }

    public function getSoldierList(): void {
        $this->getListHead();

        $this->getListBody();

        $this->getListFooter();
    }

    private function getListHead(): void {
        ?>
        <table class="soldier-list-table">
            <thead class="soldier-list-head">
                <tr>
                    <th class="soldier-table-country sorter-text"></th>
                    <th class="soldier-table-class sorter-text">Class</th>
                    <th class="soldier-table-rank sorter-text">Rank</th>
                    <th class="soldier-table-name sorter-text">Name</th>
                    <th class="soldier-table-shots">Shots</th>
                    <th class="soldier-table-ow">Overwatch</th>
                    <th class="soldier-table-other">Other</th>
                    <th class="soldier-table-damage">Damage</th>
                    <th class="soldier-table-kills">Kills</th>
                    <th class="soldier-table-eas">EAS</th>
                </tr>
            </thead>
            <tbody>
        <?php
    }

    private function getListBody(): void {
        $query = "SELECT soldier.*, xrank.level 
			FROM xcom_soldier as soldier
				INNER JOIN xcom_rank as xrank ON soldier.rank_id = xrank.id
			ORDER BY CASE soldier.killed
				WHEN 0 THEN 1
				WHEN 2 THEN 2
				WHEN 1 THEN 3
				ELSE 4 END, xrank.level DESC, soldier.last_name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        while ($row = $queryResult->fetch()) {

            $soldier = new Soldier();
            $soldier->getSoldier(intval($row['id']));

            $classInfo = $soldier->soldierClass();
            $rankInfo = $soldier->soldierRank();
            $statsInfo = $soldier->soldierStats();

            if ($soldier->nickname == "") {
                $soldierName = $soldier->firstName . ' ' . $soldier->lastName;
            } else {
                $soldierName = $soldier->firstName . ' "' . $soldier->nickname . '" ' . $soldier->lastName;
            }

            $flag = $classInfo['name'] == "Reaper" ? 'reapers' : '';
            $flag = $classInfo['name'] == "Skirmisher" ? 'skirmishers' : $flag;
            $flag = $classInfo['name'] == "Templar" ? 'templars' : $flag;
            $flag = $classInfo['name'] == "Spark" ? 'spark' : $flag;
            $flag = $classInfo['name'] == "Spark: Artillery" ? 'spark' : $flag;
            $flag = $classInfo['name'] == "Spark: Pioneer" ? 'spark' : $flag;
            $flag = $classInfo['name'] == "Spark: Infiltrator" ? 'spark' : $flag;
            $flag = empty($flag) ? str_replace(" ", "-", strtolower($soldier->country)) : $flag;

            //check for black advent flag
            if($flag == "advent") {
                $flag = "advent-dark";
            }

            if ($statsInfo['aliens_killed'] > 0 and $statsInfo['lost_killed'] == 0) {
                $killedText = $statsInfo['aliens_killed'] . " (A)";
            } elseif ($statsInfo['aliens_killed'] == 0 and $statsInfo['lost_killed'] > 0) {
                $killedText = $statsInfo['lost_killed'] . " (L)";
            } elseif ($statsInfo['aliens_killed'] > 0 and $statsInfo['lost_killed'] > 0) {
                $killedText = $statsInfo['aliens_killed'] . " (A)<br />" . $statsInfo['lost_killed'] . " (L)";
            } else {
                $killedText = "";
            }
            $killedTotal = $statsInfo['aliens_killed'] + $statsInfo['lost_killed'];

            $this->overall['shots_taken'] += $statsInfo['shots_taken'];
            $this->overall['shots_hit'] += $statsInfo['shots_hit'];
            $this->overall['overwatch_taken'] += $statsInfo['overwatch_taken'];
            $this->overall['overwatch_hit'] += $statsInfo['overwatch_hit'];
            $this->overall['other_taken'] += $statsInfo['other_taken'];
            $this->overall['other_hit'] += $statsInfo['other_hit'];
            $this->overall['damage'] += $statsInfo['damage'];
            $this->overall['aliens_killed'] += $statsInfo['aliens_killed'];
            $this->overall['lost_killed'] += $statsInfo['lost_killed'];
            $this->overall['eas'] += $statsInfo['eas'] ?? 0;

            if ($soldier->killed == 1) {
                $livingClass = " alert-danger";
            } elseif ($soldier->killed == 2) {
                $livingClass = " alert-warning";
            } else {
                $livingClass = "";
            }

            $countryName = ucwords(str_replace("-", " ", $flag));
        ?>
        <tr class="soldier-list-row<?php echo $livingClass; ?>">
            <td class="list-row-content flag" data-label="Country" data-text="<?php echo $countryName; ?>"><img src="/img/flags/<?php echo $flag; ?>.png" alt="<?php echo $countryName; ?>" title="<?php echo $countryName; ?>"></td>
            <td class="list-row-content class" data-label="Class" data-text="<?php echo $classInfo['name']; ?>"><img src="<?php echo $classInfo['icon'] ?>" alt="<?php echo $classInfo['name']; ?>" title="<?php echo $classInfo['name']; ?>" /></td>
            <td class="list-row-content rank" data-label="Rank" data-text="<?php echo $rankInfo['level']; ?>"><img src="<?php echo $rankInfo['icon']; ?>" alt="<?php echo $rankInfo['name']; ?>" title="<?php echo $rankInfo['name']; ?>" /><span class="rank-short"><?php echo $rankInfo['short']; ?></span></td>
            <td class="list-row-content name" data-label="Name" data-text="<?php echo $soldier->lastName; ?>."><a href="<?php echo '/soldier/'.str_replace(" ", "-", strtolower($soldier->firstName))."~".str_replace(" ", "-",strtolower($soldier->lastName))."/"; ?>"><?php echo $soldierName ?></a></td>
            <td class="list-row-content shots" data-label="Shots" data-text="<?php echo $statsInfo['shots_pct']; ?>">
                <?php
                if($statsInfo['shots_taken'] > 0) {
                    echo $statsInfo['shots_hit'].'/'.$statsInfo['shots_taken'].'<span class="shot-pct"> ('.$statsInfo['shots_pct'].'%)</span>';
                }
                ?>
            </td>
            <td class="list-row-content overwatch" data-label="Overwatch" data-text="<?php echo $statsInfo['overwatch_pct']; ?>">
                <?php
                if($statsInfo['overwatch_taken'] > 0) {
                    echo $statsInfo['overwatch_hit'].'/'.$statsInfo['overwatch_taken'].'<span class="shot-pct"> ('.$statsInfo['overwatch_pct'].'%)</span>';
                }
                ?>
            </td>
            <td class="list-row-content other" data-label="Other" data-text="<?php echo $statsInfo['other_pct']; ?>">
                <?php
                if($statsInfo['other_taken'] > 0) {
                    echo $statsInfo['other_hit'].'/'.$statsInfo['other_taken'].'<span class="shot-pct"> ('.$statsInfo['other_pct'].'%)</span>';
                }
                ?>
            </td>
            <td class="list-row-content damage" data-label="Damage" data-text="<?php echo $statsInfo['damage']; ?>"><?php echo $statsInfo['damage']; ?></td>
            <td class="list-row-content kills" data-label="Kills" data-text="<?php echo $killedTotal; ?>"><?php echo $killedText; ?></td>
            <td class="list-row-content eas" data-label="EAS" data-text="<?php echo $statsInfo['eas'] ?? 0; ?>"><?php echo number_format($statsInfo['eas'] ?? 0,2); ?></td>
        </tr>
        <?php
        }
    }

    private function getListFooter(): void {
        $this->overall['shots_pct'] = "N/A";
        $this->overall['overwatch_pct'] = "N/A";
        $this->overall['other_pct'] = "N/A";

        if($this->overall['shots_taken'] > 0) {
            $this->overall['shots_pct'] = (round($this->overall['shots_hit'] / $this->overall['shots_taken'], 2)) * 100;
        }
        if($this->overall['overwatch_taken'] > 0) {
            $this->overall['overwatch_pct'] = (round($this->overall['overwatch_hit'] / $this->overall['overwatch_taken'], 2)) * 100;
        }
        if($this->overall['other_taken'] > 0) {
            $this->overall['other_pct'] = (round($this->overall['other_hit'] / $this->overall['other_taken'], 2)) * 100;
        }

        if($this->overall['aliens_killed'] > 0 and $this->overall['lost_killed'] == 0) {
            $this->overall['killed_text'] = $this->overall['aliens_killed']." (A)";
        }
        elseif($this->overall['aliens_killed'] == 0 and $this->overall['lost_killed'] > 0) {
            $this->overall['killed_text'] = $this->overall['lost_killed']." (L)";
        }
        elseif($this->overall['aliens_killed'] > 0 and $this->overall['lost_killed'] > 0) {
            $this->overall['killed_text'] = $this->overall['aliens_killed']." (A)<br />".$this->overall['lost_killed']." (L)";
        }
        else {
            $this->overall['killed_text'] = "";
        }

        ?>
        </tbody>
        <tfoot>
        <tr class="soldier-list-footer">
            <td class="list-row-content flag"><img src="/img/flags/xcom.png" alt="XCOM Flag"></td>
            <td class="list-row-content class"></td>
            <td class="list-row-content rank"></td>
            <td class="list-row-content name"><strong>Team Total</strong></td>
            <td class="list-row-content shots"><strong><?php echo $this->overall['shots_hit']."/".$this->overall['shots_taken']." (".$this->overall['shots_pct']."%)"; ?></strong></td>
            <td class="list-row-content overwatch"><strong><?php echo $this->overall['overwatch_hit']."/".$this->overall['overwatch_taken']." (".$this->overall['overwatch_pct']."%)"; ?></strong></td>
            <td class="list-row-content other"><strong><?php echo $this->overall['other_hit']."/".$this->overall['other_taken']." (".$this->overall['other_pct']."%)"; ?></strong></td>
            <td class="list-row-content damage"><strong><?php echo $this->overall['damage']; ?></strong></td>
            <td class="list-row-content kills"><strong><?php echo $this->overall['killed_text']; ?></strong></td>
            <td class="list-row-content eas"><strong><?php echo number_format($this->overall['eas'],2); ?></strong></td>
        </tr>
        </tfoot>
        </table>
    <?php
    }
}